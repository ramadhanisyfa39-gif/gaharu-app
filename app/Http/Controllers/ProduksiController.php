<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\MasterBarang;
use App\Models\ResepBahanBaku;
use App\Models\StokGudang;
use App\Models\ProduksiPesanan;
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
     * Halaman Riwayat / Daftar Hasil Produksi
     */
    public function index()
    {
        $riwayatProduksi = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->leftJoin('master_barang', 'produksi_detail.produk_id', '=', 'master_barang.id')
            ->select(
                'produksi_detail.*',
                'produksi.kode_produksi',
                'produksi.tanggal_mulai as tanggal',
                'master_barang.nama as nama_produk',
                DB::raw('(SELECT wo.kode_wo
                          FROM work_order wo
                          JOIN work_order_detail wod
                            ON wod.work_order_id = wo.id
                          WHERE wod.pesanan_id = produksi.pesanan_id
                          LIMIT 1) as kode_wo')
            )
            ->orderBy('produksi_detail.id', 'desc')
            ->get();

        return view('produksi.index', compact('riwayatProduksi'));
    }

    /**
     * Halaman Form Input Produksi
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

        return view('produksi.create', compact(
            'workOrders',
            'gudangs',
            'selectedWoId',
            'items'
        ));
    }

    /**
     * Proses Simpan Data Produksi
     * FIFO + BBB + BTKL + BOP + Alokasi Pesanan
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
            $gudangBahanId = 3;
            $gudangHasilId = 3;

            $fifoService = app(\App\Services\FifoService::class);

            /*
            |--------------------------------------------------------------------------
            | Ambil semua pesanan yang tergabung dalam Work Order
            |--------------------------------------------------------------------------
            */
            $pesananIds = DB::table('work_order_detail')
                ->where('work_order_id', $request->work_order_id)
                ->pluck('pesanan_id')
                ->unique()
                ->values()
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Ambil pesanan pertama agar kode WO masih dapat ditampilkan
            | pada riwayat produksi.
            |--------------------------------------------------------------------------
            */
            $pesananIdUtama = !empty($pesananIds) ? $pesananIds[0] : null;

            /*
            |--------------------------------------------------------------------------
            | Generate kode produksi
            |--------------------------------------------------------------------------
            */
            $kodeProduksi = 'PRD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            /*
            |--------------------------------------------------------------------------
            | Simpan header produksi
            |--------------------------------------------------------------------------
            */
            $produksiId = DB::table('produksi')->insertGetId([
                'kode_produksi'   => $kodeProduksi,
                'pesanan_id'      => $pesananIdUtama,
                'tanggal_mulai'   => $request->tanggal_produksi,
                'tanggal_selesai' => $request->tanggal_produksi,
                'status_produksi' => 'Selesai',
                'gudang_bahan_id' => $gudangBahanId,
                'gudang_hasil_id' => $gudangHasilId,
                'created_by'      => auth()->id() ?? 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Simpan detail hasil produksi dan hitung HPP
            |--------------------------------------------------------------------------
            */
            foreach ($request->produk_id as $key => $produkId) {
                $qtyHasil = floatval($request->qty_hasil[$key]);

                if ($qtyHasil <= 0) {
                    continue;
                }

                $produk = MasterBarang::find($produkId);

                if (!$produk) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->with('error', "ID Produk {$produkId} tidak valid.");
                }

                if (is_null($produk->resep_id)) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->with('error', "Produk '{$produk->nama}' belum memiliki resep.");
                }

                $totalBbbProduk = 0;

                /*
                |--------------------------------------------------------------------------
                | A. Hitung dan kurangi bahan baku dengan FIFO
                |--------------------------------------------------------------------------
                */
                $resepItems = ResepBahanBaku::where(
                    'resep_id',
                    $produk->resep_id
                )->get();

                foreach ($resepItems as $item) {
                    $qtyButuh = $item->qty_bahan * $qtyHasil;

                    $fifoResult = $fifoService->consumeFIFO(
                        $item->bahan_id,
                        $qtyButuh,
                        $gudangBahanId
                    );

                    foreach ($fifoResult as $layer) {
                        $totalBbbProduk +=
                            floatval($layer['qty_keluar']) *
                            floatval($layer['harga_per_qty']);
                    }

                    $stokBahanGlobal = StokGudang::where(
                        'gudang_id',
                        $gudangBahanId
                    )
                        ->where('barang_id', $item->bahan_id)
                        ->first();

                    if ($stokBahanGlobal) {
                        $stokBahanGlobal->decrement('jumlah', $qtyButuh);
                    } else {
                        StokGudang::create([
                            'gudang_id' => $gudangBahanId,
                            'barang_id' => $item->bahan_id,
                            'jumlah'    => 0 - $qtyButuh,
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | B. Hitung BTKL dan BOP
                |--------------------------------------------------------------------------
                */
                $totalBtklBop = 0;

                $biayaTambahan = DB::table('resep_btkl_bop')
                    ->where('produk_id', $produkId)
                    ->first();

                if ($biayaTambahan && $biayaTambahan->output_qty > 0) {
                    $btklPerBatch = floatval($biayaTambahan->btkl_per_batch);
                    $bopPerBatch  = floatval($biayaTambahan->bop_per_batch);
                    $outputBatch  = floatval($biayaTambahan->output_qty);

                    $biayaPerItem = ($btklPerBatch + $bopPerBatch) / $outputBatch;

                    $totalBtklBop = $biayaPerItem * $qtyHasil;
                }

                /*
                |--------------------------------------------------------------------------
                | C. Hitung total HPP
                |--------------------------------------------------------------------------
                */
                $hppKeseluruhan = $totalBbbProduk + $totalBtklBop;

                /*
                |--------------------------------------------------------------------------
                | D. Tambahkan stok barang jadi
                |--------------------------------------------------------------------------
                */
                $stokBarangJadi = StokGudang::where(
                    'gudang_id',
                    $gudangHasilId
                )
                    ->where('barang_id', $produkId)
                    ->first();

                if ($stokBarangJadi) {
                    $stokBarangJadi->increment('jumlah', $qtyHasil);
                } else {
                    StokGudang::create([
                        'gudang_id' => $gudangHasilId,
                        'barang_id' => $produkId,
                        'jumlah'    => $qtyHasil,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Simpan detail produksi
                |--------------------------------------------------------------------------
                */
                DB::table('produksi_detail')->insert([
                    'produksi_id' => $produksiId,
                    'produk_id'   => $produkId,
                    'qty'         => $qtyHasil,
                    'hpp_total'   => $hppKeseluruhan,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | E. Buat alokasi hasil produksi ke setiap pesanan dalam WO
                |--------------------------------------------------------------------------
                */
                $hppPerUnit = $hppKeseluruhan / $qtyHasil;

                $detailPesananWO = WorkOrderDetail::where('work_order_id', $request->work_order_id)
                    ->where('produk_id', $produkId)
                    ->get();

                foreach ($detailPesananWO as $detailWO) {
                    ProduksiPesanan::create([
                        'produksi_id'       => $produksiId,
                        'pesanan_id'        => $detailWO->pesanan_id,
                        'produk_id'         => $produkId,
                        'qty_alokasi'       => $detailWO->qty_rencana,
                        'qty_terkirim'      => 0,
                        'hpp_per_unit'      => $hppPerUnit,
                        'total_hpp_alokasi' => $detailWO->qty_rencana * $hppPerUnit,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update status Work Order
            |--------------------------------------------------------------------------
            */
            DB::table('work_order')
                ->where('id', $request->work_order_id)
                ->update([
                    'status_wo'  => 'Selesai',
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Update seluruh pesanan yang ada dalam Work Order menjadi Siap kirim
            |--------------------------------------------------------------------------
            */
            if (!empty($pesananIds)) {
                DB::table('pesanan')
                    ->whereIn('id', $pesananIds)
                    ->update([
                        'status_pesanan' => 'Siap kirim',
                        'updated_at'     => now(),
                    ]);
            }

            DB::commit();

            return redirect()
                ->route('produksi.index')
                ->with(
                    'success',
                    'Produksi berhasil disimpan, stok dan HPP diperbarui, alokasi pesanan dibuat, serta seluruh pesanan dalam WO menjadi Siap kirim.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal Simpan! Pesan: ' . $e->getMessage());
        }
    }
}