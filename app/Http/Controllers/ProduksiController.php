<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\MasterBarang;
use App\Models\ResepBahanBaku;
use App\Models\StokGudang;
use Illuminate\Support\Facades\DB;

class ProduksiController extends Controller
{
    protected $fifoService;

    public function __construct()
    {
        // Aktifkan jika FifoService kamu siap
        // $this->fifoService = app(\App\Services\FifoService::class); 
    }

    /**
     * Halaman Riwayat / Daftar Hasil Produksi (GET /produksi)
     */
    public function index()
    {
        // REVISI: Menggunakan Subquery untuk mengambil kode_wo agar terhindar dari ONLY_FULL_GROUP_BY
        $riwayatProduksi = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->leftJoin('master_barang', 'produksi_detail.produk_id', '=', 'master_barang.id')
            ->select(
                'produksi_detail.*', 
                'produksi.kode_produksi', 
                'produksi.tanggal_mulai as tanggal',
                'master_barang.nama as nama_produk',
                // Ambil kode_wo langsung lewat subquery berdasarkan pesanan_id
                DB::raw('(SELECT wo.kode_wo 
                          FROM work_order wo 
                          JOIN work_order_detail wod ON wod.work_order_id = wo.id 
                          WHERE wod.pesanan_id = produksi.pesanan_id 
                          LIMIT 1) as kode_wo')
            )
            ->orderBy('produksi_detail.id', 'desc')
            ->get();

        return view('produksi.index', compact('riwayatProduksi'));
    }
    
    /**
     * Halaman Form Input (GET /produksi/create)
     */
    public function create(Request $request)
    {
        $workOrders = WorkOrder::where('status_wo', 'Diproses')->get();
        $gudangs = DB::table('master_gudang')->get();

        $selectedWoId = $request->get('work_order_id');
        $items = collect(); 

        if ($selectedWoId) {
            $items = WorkOrderDetail::where('work_order_id', $selectedWoId)
                ->select('produk_id', DB::raw('sum(qty_rencana) as total_target'))
                ->with('produk')
                ->groupBy('produk_id') 
                ->get();
        }

        return view('produksi.create', compact('workOrders', 'gudangs', 'selectedWoId', 'items'));
    }

    /**
     * Proses Simpan Data Produksi (Header & Detail) - FIFO MURNI + BTKL + BOP Proporsional
     */
    public function store(Request $request)
    {
        $request->validate([
            'work_order_id'    => 'required',
            'tanggal_produksi' => 'required|date',
            'produk_id'        => 'required|array',
            'qty_hasil'        => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $gudangBahanId = 3; // Gudang B2B
            $gudangHasilId = 3; // Gudang B2B

            $fifoService = app(\App\Services\FifoService::class);

            // 1. Ambil pesanan_id dari work_order_detail
            $woDetail = DB::table('work_order_detail')
                ->where('work_order_id', $request->work_order_id)
                ->first();
            $pesananId = $woDetail ? $woDetail->pesanan_id : null;

            // 2. Generate kode produksi
            $kodeProduksi = 'PRD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // 3. Simpan HEADER produksi
            $produksiId = DB::table('produksi')->insertGetId([
                'kode_produksi'    => $kodeProduksi,
                'pesanan_id'       => $pesananId,
                'tanggal_mulai'    => $request->tanggal_produksi,
                'tanggal_selesai'  => $request->tanggal_produksi,
                'status_produksi'  => 'Selesai',
                'gudang_bahan_id'  => $gudangBahanId,
                'gudang_hasil_id'  => $gudangHasilId,
                'created_by'       => auth()->id() ?? 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // 4. Looping item untuk tabel DETAIL
            foreach ($request->produk_id as $key => $produkId) {
                $qtyHasil = floatval($request->qty_hasil[$key]);
                if ($qtyHasil <= 0) continue;

                $produk = \App\Models\MasterBarang::find($produkId);
                if (!$produk) {
                    DB::rollBack();
                    return redirect()->back()->with('error', "ID Produk {$produkId} tidak valid.");
                }

                if (is_null($produk->resep_id)) {
                    DB::rollBack();
                    return redirect()->back()->with('error', "Produk '{$produk->nama}' tidak punya resep_id.");
                }

                $totalBbbProduk = 0; 

                // --- A. KALKULASI BAHAN BAKU (FIFO) ---
                $resepItems = \App\Models\ResepBahanBaku::where('resep_id', $produk->resep_id)->get();
                if ($resepItems->count() > 0) {
                    foreach ($resepItems as $item) {
                        $qtyButuh = $item->qty_bahan * $qtyHasil;
                        
                        // Eksekusi pemotongan FIFO
                        $fifoResult = $fifoService->consumeFIFO($item->bahan_id, $qtyButuh, $gudangBahanId);
                        
                        foreach ($fifoResult as $layer) {
                            $totalBbbProduk += (floatval($layer['qty_keluar']) * floatval($layer['harga_per_qty']));
                        }
                        
                        // Potong stok global
                        $stokBahanGlobal = \App\Models\StokGudang::where('gudang_id', $gudangBahanId)
                                                                     ->where('barang_id', $item->bahan_id)
                                                                     ->first();
                        if ($stokBahanGlobal) {
                            $stokBahanGlobal->decrement('jumlah', $qtyButuh);
                        } else {
                            \App\Models\StokGudang::create([
                                'gudang_id' => $gudangBahanId,
                                'barang_id' => $item->bahan_id,
                                'jumlah'    => 0 - $qtyButuh
                            ]);
                        }
                    }
                }

                // --- B. KALKULASI BTKL & BOP PROPORSIONAL ---
                $totalBtklBop = 0;
                
                // Ambil data biaya berdasarkan produk_id
                $biayaTambahan = DB::table('resep_btkl_bop')
                                   ->where('produk_id', $produkId)
                                   ->first(); // Menggunakan first() karena asumsinya 1 produk = 1 setting biaya

                if ($biayaTambahan && $biayaTambahan->output_qty > 0) {
                    $btklPerBatch = floatval($biayaTambahan->btkl_per_batch);
                    $bopPerBatch  = floatval($biayaTambahan->bop_per_batch);
                    $outputBatch  = floatval($biayaTambahan->output_qty);
                    
                    // Hitung biaya gabungan per 1 Qty
                    $biayaPerItem = ($btklPerBatch + $bopPerBatch) / $outputBatch;
                    
                    // Total Biaya = Biaya per 1 Qty x Qty Produksi Nyata
                    $totalBtklBop = $biayaPerItem * $qtyHasil;
                }

                // --- C. GABUNGKAN TOTAL HPP (BBB + BTKL + BOP) ---
                $hppKeseluruhan = $totalBbbProduk + $totalBtklBop;

                // --- D. TAMBAH STOK BARANG JADI ---
                $stokBarangJadi = \App\Models\StokGudang::where('gudang_id', $gudangHasilId)
                                                             ->where('barang_id', $produkId)
                                                             ->first();
                if ($stokBarangJadi) {
                    $stokBarangJadi->increment('jumlah', $qtyHasil);
                } else {
                    \App\Models\StokGudang::create([
                        'gudang_id' => $gudangHasilId,
                        'barang_id' => $produkId,
                        'jumlah'    => $qtyHasil
                    ]);
                }

                // Simpan ke produksi_detail
                DB::table('produksi_detail')->insert([
                    'produksi_id' => $produksiId,
                    'produk_id'   => $produkId,
                    'qty'         => $qtyHasil,
                    'hpp_total'   => $hppKeseluruhan, 
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }

            // 5. UPDATE STATUS WORK ORDER & STATUS PESANAN
            DB::table('work_order')
                ->where('id', $request->work_order_id)
                ->update([
                    'status_wo'  => 'Selesai',
                    'updated_at' => now()
                ]);

            if ($pesananId) {
                DB::table('pesanan')
                    ->where('id', $pesananId)
                    ->update([
                        'status_pesanan' => 'Siap kirim',
                        'updated_at'     => now()
                    ]);
            }

            DB::commit();
            return redirect()->route('produksi.index')
                             ->with('success', 'Produksi Berhasil! Stok batch terpotong FIFO, HPP (BBB+BTKL+BOP) tercatat komprehensif, dan status pesanan diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal Simpan! Pesan: ' . $e->getMessage());
        }
    }
}