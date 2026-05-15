<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Produksi;
use App\Models\ProduksiDetail;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\StokGudang;
use App\Models\Pesanan;
use App\Models\PesananDetail;
use App\Models\ResepBahanBaku;
use App\Models\MasterBarang;

class ProduksiController extends Controller
{
    public function index()
    {
        $produksi = Produksi::with(['pesanan.customer'])->latest()->get();
        return view('produksi.index', compact('produksi'));
    }

    public function create()
    {
        // Menampilkan WO yang siap diproduksi (sudah dipindah bahannya)
        $workOrders = WorkOrder::whereIn('status_wo', ['Draft', 'Diproses'])->get();
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
        dd($request->all());
        $request->validate([
            'work_order_id' => 'required',
            'produk_id'     => 'required|array',
            'qty_hasil'     => 'required|array',
        ]);
    
        DB::beginTransaction();
        try {
            $wo = WorkOrder::findOrFail($request->work_order_id);
            
            $firstDetail = WorkOrderDetail::where('work_order_id', $wo->id)->first();
            $pesananId = $firstDetail ? $firstDetail->pesanan_id : null;
    
            // 1. Simpan Header Produksi
            $produksi = Produksi::create([
                'kode_produksi'   => 'PRD-' . date('YmdHis'),
                'pesanan_id'      => $pesananId,
                'tanggal_mulai'   => now(),
                'tanggal_selesai' => now(),
                'status_produksi' => 'Selesai',
                'gudang_bahan_id' => 3, 
                'gudang_hasil_id' => 3, 
                'created_by'      => auth()->id()
            ]);
    
            foreach ($request->produk_id as $key => $produkId) {
                $qtyHasil = $request->qty_hasil[$key];
    
                // 2. Simpan Detail Produksi
                ProduksiDetail::create([
                    'produksi_id' => $produksi->id,
                    'produk_id'   => $produkId,
                    'qty'         => $qtyHasil
                ]);
    
                // 3. Update Stok Produk Jadi di B2B (ID 3)
                $stokJadi = StokGudang::firstOrCreate(
                    ['gudang_id' => 3, 'barang_id' => $produkId],
                    ['jumlah' => 0]
                );
                $stokJadi->increment('jumlah', $qtyHasil);
    
                // 4. Potong Bahan Baku di B2B (ID 3)
                $produk = MasterBarang::find($produkId);
                $resepItems = ResepBahanBaku::where('resep_id', $produk->resep_id)->get();
    
                if ($resepItems->count() > 0) {
                    foreach ($resepItems as $item) {
                        $qtyButuh = $item->qty_bahan * $qtyHasil;
                        $stokBahan = StokGudang::where('gudang_id', 3)
                                              ->where('barang_id', $item->bahan_id)
                                              ->first();
    
                        if ($stokBahan) {
                            $stokBahan->decrement('jumlah', $qtyButuh);
                        } else {
                            StokGudang::create([
                                'gudang_id' => 3,
                                'barang_id' => $item->bahan_id,
                                'jumlah'    => 0 - $qtyButuh
                            ]);
                        }
                    }
                }
            }
    
            // 5. Update Status Work Order
            $wo->update(['status_wo' => 'Selesai']);
    
            // --- TAMBAHAN LOGIKA UPDATE STATUS PESANAN ---
            if ($pesananId) {
                $pesanan = Pesanan::find($pesananId);
                if ($pesanan) {
                    // Hitung total Qty yang diminta di Pesanan ini
                    $totalMinta = PesananDetail::where('pesanan_id', $pesananId)->sum('qty');
    
                    // Hitung total Qty yang sudah pernah diproduksi untuk Pesanan ini
                    $totalJadi = ProduksiDetail::whereHas('produksi', function($q) use ($pesananId) {
                        $q->where('pesanan_id', $pesananId);
                    })->sum('qty');
    
                    // Jika hasil produksi sudah memenuhi atau melebihi jumlah pesanan
                    if ($totalJadi >= $totalMinta) {
                        $pesanan->update(['status_pesanan' => 'siap kirim']);
                    }
                }
            }
            // ----------------------------------------------
    
            DB::commit();
            return redirect()->route('produksi.index')->with('success', 'Data Produksi Berhasil Disimpan & Status Pesanan Diperbarui!');
    
        } catch (\Exception $e) {
            DB::rollback();
            dd("Gagal Simpan! Pesan Error: " . $e->getMessage()); 
        }
    }
}