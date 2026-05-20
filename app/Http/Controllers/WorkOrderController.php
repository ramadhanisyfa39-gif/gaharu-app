<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\PesananDetail;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\StokGudang;
use App\Models\MasterBarang;
use App\Models\ResepBahanBaku;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index()
    {
        $wo = WorkOrder::with(['details', 'pesanan.customer'])->latest()->get();
    
        // Tampilkan pesanan yang status bayarnya DP atau Lunas untuk dibuatkan WO
        $pesanan = Pesanan::with(['details.produk', 'customer'])
                    ->where('status_pesanan', 'pending')
                    ->whereIn('status_pembayaran', ['DP', 'Lunas']) 
                    ->latest()
                    ->get();
    
        return view('work_order.index', compact('wo', 'pesanan'));
    }

    public function create($id)
    {
        $pesanan = Pesanan::with(['details.produk', 'customer'])->findOrFail($id);
        return view('work_order.create', compact('pesanan'));
    }

    public function store(Request $request)
    {
        $pesanan = Pesanan::findOrFail($request->pesanan_id);

        if ($pesanan->status_pembayaran == 'Belum Bayar') {
            return back()->with('error', 'Gagal! Pesanan ini belum membayar DP.');
        }

        DB::beginTransaction();
        try {
            $wo = WorkOrder::create([
                'pesanan_id' => $request->pesanan_id,
                'kode_wo'    => $request->kode_wo,
                'tanggal_wo' => $request->tanggal_wo,
                'status_wo'  => 'Draft',
                'catatan'    => $request->catatan,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->produk_id as $key => $produk_id) {
                if ($request->qty_rencana[$key] <= 0) continue;

                WorkOrderDetail::create([
                    'work_order_id' => $wo->id,
                    'pesanan_id'    => $request->pesanan_id,
                    'produk_id'     => $produk_id,
                    'qty_rencana'   => $request->qty_rencana[$key],
                ]);
            }

            DB::commit();
            return redirect()->route('wo.index')->with('success', 'Work Order berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function reviewMassal(Request $request)
    {
        $request->validate(['detail_ids' => 'required|array|min:1']);

        $details = PesananDetail::with(['pesanan.customer', 'produk'])
                    ->whereIn('id', $request->detail_ids)
                    ->get();

        foreach ($details as $detail) {
            $qtySudahWO = WorkOrderDetail::where('pesanan_id', $detail->pesanan_id)
                            ->where('produk_id', $detail->produk_id)
                            ->sum('qty_rencana');
            $detail->sisa_qty = $detail->qty - $qtySudahWO;
        }

        return view('work_order.review_massal', compact('details'));
    }

    public function storeMassal(Request $request)
    {
        $cekBayar = Pesanan::whereIn('id', $request->pesanan_id)
                            ->where('status_pembayaran', 'Belum Bayar')
                            ->exists();
    
        if ($cekBayar) {
            return redirect()->route('wo.index')->with('error', 'Salah satu pesanan belum membayar DP!');
        }

        DB::beginTransaction();
        try {
            $wo = WorkOrder::create([
                'kode_wo'    => 'WO-BATCH-' . strtoupper(bin2hex(random_bytes(3))),
                'tanggal_wo' => now(),
                'status_wo'  => 'Draft',
                'created_by' => auth()->id(),
                'catatan'    => 'Dibuat secara massal/gabungan',
            ]);

            foreach ($request->pesanan_id as $index => $p_id) {
                if ($request->qty_rencana[$index] > 0) {
                    WorkOrderDetail::create([
                        'work_order_id' => $wo->id,
                        'pesanan_id'    => $p_id,
                        'produk_id'     => $request->produk_id[$index],
                        'qty_rencana'   => $request->qty_rencana[$index],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('wo.index')->with('success', 'Work Order Gabungan berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('wo.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

public function show($id)
{
    $wo = WorkOrder::with([
        'details.pesanan.customer', 
        'details.produk.resep.bahan' // Harus memuat urutan ini
    ])->findOrFail($id);

    return view('work_order.show', compact('wo'));
}

    /**
     * FUNGSI KRUSIAL: Kirim Produksi & Transfer Stok Bahan Baku
     * Dari Gudang Utama (ID: 1) ke Gudang Produksi (ID: 2)
     */
    public function kirimKeProduksi($id)
    {
        $wo = \App\Models\WorkOrder::with('details.produk.resep.bahan')->findOrFail($id);
        
        \DB::beginTransaction();
        try {
            // 1. BUAT DOKUMEN PENGELUARAN (Status: 'Draft')
            // Ini yang nanti di-approve bagian persediaan/gudang
            $pengeluaran = \App\Models\PengeluaranBahanBaku::create([
                'kode_pengeluaran' => 'REQ-' . date('Ymd') . '-' . strtoupper(\Str::random(4)),
                'tanggal'          => now(),
                'gudang_id'        => 4, 
                'status'           => 'Draft', 
                'keterangan'       => 'Permintaan bahan baku untuk ' . $wo->kode_wo,
                'created_by'       => auth()->id(),
            ]);
    
            // 2. MASUKKAN DETAIL BAHAN
            foreach ($wo->details as $detail) {
                if ($detail->produk && $detail->produk->resep) {
                    foreach ($detail->produk->resep as $resep) {
                        \App\Models\PengeluaranBahanBakuDetail::create([
                            'pengeluaran_id' => $pengeluaran->id,
                            'barang_id'      => $resep->bahan_id,
                            'qty'            => $resep->qty_bahan * $detail->qty_rencana,
                            'satuan'         => $resep->bahan->satuan ?? 'gr',
                        ]);
                    }
                }
            }
    
            // 3. UPDATE STATUS WO KE 'Diproses'
            // Status ini jauh lebih tepat secara alur kerja
            $wo->update(['status_wo' => 'Diproses']);
    
            \DB::commit();
            return redirect()->back()->with('success', 'Permintaan bahan dikirim! Status WO kini: Diproses.');
    
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}