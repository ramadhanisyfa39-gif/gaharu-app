<?php
// app/Http/Controllers/ProduksiController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Produksi;
use App\Models\ProduksiDetail;

use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;

use App\Models\StokGudang;
use App\Models\TransaksiStok;

use App\Models\ResepBahanBaku;
use App\Models\MasterBarang;

class ProduksiController extends Controller
{
    public function index()
    {
        $produksi = Produksi::latest()->get();

        return view('produksi.index', compact('produksi'));
    }

    public function create()
    {
        $workOrders = WorkOrder::where('status_wo', 'Diproses')->get();

        return view('produksi.create', compact('workOrders'));
    }

    public function getWoDetail($id)
    {
        $detail = WorkOrderDetail::with('produk')
                    ->where('work_order_id', $id)
                    ->get();

        return response()->json($detail);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $wo = WorkOrder::findOrFail($request->work_order_id);

            $kode = 'PRD-' . date('YmdHis');

            $pesananId = WorkOrderDetail::where('work_order_id', $wo->id)
                            ->first()
                            ->pesanan_id;

            $produksi = Produksi::create([
                'kode_produksi' => $kode,
                'pesanan_id' => $pesananId,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => now(),
                'status_produksi' => 'Selesai',

                // GANTI sesuai ID gudangmu
                'gudang_bahan_id' => 2,
                'gudang_hasil_id' => 2,

                'created_by' => auth()->id()
            ]);

            foreach($request->produk_id as $key => $produkId){

                $qtyHasil = $request->qty_hasil[$key];

                // =====================================
                // SIMPAN PRODUKSI DETAIL
                // =====================================

                ProduksiDetail::create([
                    'produksi_id' => $produksi->id,
                    'produk_id' => $produkId,
                    'qty' => $qtyHasil
                ]);

                // =====================================
                // TAMBAH STOK BARANG JADI
                // =====================================

                $stokBarangJadi = StokGudang::firstOrCreate(
                    [
                        'gudang_id' => 2,
                        'barang_id' => $produkId
                    ],
                    [
                        'jumlah' => 0
                    ]
                );

                $stokBarangJadi->jumlah += $qtyHasil;
                $stokBarangJadi->save();

                // =====================================
                // TRANSAKSI STOK MASUK
                // =====================================

                TransaksiStok::create([
                    'tanggal' => now(),
                    'tipe' => 'Masuk',
                    'source_type' => 'Produksi',
                    'source_id' => $produksi->id,
                    'gudang_asal_id' => null,
                    'gudang_tujuan_id' => 2,
                    'barang_id' => $produkId,
                    'qty' => $qtyHasil,
                    'total_harga' => 0,
                    'created_by' => auth()->id()
                ]);

                // =====================================
                // AMBIL DATA PRODUK
                // =====================================

                $produk = MasterBarang::find($produkId);

                // pastikan produk punya resep_id
                if($produk && $produk->resep_id){

                    $resep = ResepBahanBaku::where(
                        'resep_id',
                        $produk->resep_id
                    )->get();

                    foreach($resep as $bahan){

                        $qtyPakai =
                            $bahan->qty_bahan * $qtyHasil;

                        // =================================
                        // KURANGI STOK BAHAN BAKU
                        // =================================

                        $stokBahan = StokGudang::where(
                            'gudang_id',
                            2
                        )
                        ->where(
                            'barang_id',
                            $bahan->bahan_id
                        )
                        ->first();

                        if($stokBahan){

                            $stokBahan->jumlah -= $qtyPakai;
                            $stokBahan->save();
                        }

                        // =================================
                        // TRANSAKSI STOK KELUAR
                        // =================================

                        TransaksiStok::create([
                            'tanggal' => now(),
                            'tipe' => 'Keluar',
                            'source_type' => 'Produksi',
                            'source_id' => $produksi->id,
                            'gudang_asal_id' => 2,
                            'gudang_tujuan_id' => null,
                            'barang_id' => $bahan->bahan_id,
                            'qty' => $qtyPakai,
                            'total_harga' => 0,
                            'created_by' => auth()->id()
                        ]);
                    }
                }
            }

            // =====================================
            // UPDATE STATUS WO
            // =====================================

            $wo->update([
                'status_wo' => 'Selesai'
            ]);

            DB::commit();

            return redirect()
                    ->route('produksi.index')
                    ->with(
                        'success',
                        'Produksi berhasil disimpan'
                    );

        } catch (\Exception $e){

            DB::rollback();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }
}