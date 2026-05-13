<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\PesananDetail; // Tambahkan ini
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    /**
     * Halaman utama WO
     */
    public function index()
    {
        $wo = WorkOrder::with(['details', 'pesanan.customer'])->latest()->get();
    
        // HANYA tampilkan pesanan yang status bayarnya DP atau Lunas
        $pesanan = Pesanan::with(['details.produk', 'customer'])
                    ->where('status_pesanan', 'pending')
                    ->whereIn('status_pembayaran', ['DP', 'Lunas']) 
                    ->latest()
                    ->get();
    
        return view('work_order.index', compact('wo', 'pesanan'));
    }

    /**
     * Form create WO tunggal
     */
    public function create($id)
    {
        $pesanan = Pesanan::with([
                        'details.produk',
                        'customer'
                    ])->findOrFail($id);

        return view('work_order.create', compact(
            'pesanan'
        ));
    }

    /**
     * Simpan WO Tunggal
     */
public function store(Request $request)
{
    $pesanan = Pesanan::findOrFail($request->pesanan_id);

    // Proteksi: Jika belum bayar, tendang balik
    if ($pesanan->status_pembayaran == 'Belum Bayar') {
        return back()->with('error', 'Gagal! Pesanan ini belum membayar DP.');
    }

    DB::beginTransaction();
        try {
            $wo = WorkOrder::create([
                'pesanan_id' => $request->pesanan_id,
                'kode_wo' => $request->kode_wo,
                'tanggal_wo' => $request->tanggal_wo,
                'status_wo' => 'Draft',
                'catatan' => $request->catatan,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->produk_id as $key => $produk) {
                if (!isset($request->qty_rencana[$key]) || !isset($request->pesanan_id[$key])) continue;
                if ($request->qty_rencana[$key] <= 0) continue;

                WorkOrderDetail::create([
                    'work_order_id' => $wo->id,
                    'pesanan_id' => $request->pesanan_id[$key],
                    'produk_id' => $produk,
                    'qty_rencana' => $request->qty_rencana[$key],
                ]);
            }
            DB::commit();
            return redirect()->route('wo.index')->with('success', 'Work Order berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * LANGKAH 1 WO MASSAL: Menampilkan Halaman Review & Edit Qty
     */
/**
     * LANGKAH 1 WO MASSAL: Menampilkan Halaman Review & Edit Qty
     */
    public function reviewMassal(Request $request)
    {
        $request->validate([
            'detail_ids' => 'required|array|min:1',
        ], [
            'detail_ids.required' => 'Pilih minimal satu item pesanan.'
        ]);

        // Ambil data detail pesanan yang dicentang
        $details = PesananDetail::with(['pesanan.customer', 'produk'])
                    ->whereIn('id', $request->detail_ids)
                    ->get();

        // Hitung sisa yang bisa diproduksi untuk setiap item
        foreach ($details as $detail) {
            // PERUBAHAN DISINI: Kita gunakan pesanan_id dan produk_id, bukan pesanan_detail_id
            $qtySudahWO = WorkOrderDetail::where('pesanan_id', $detail->pesanan_id)
                            ->where('produk_id', $detail->produk_id)
                            ->sum('qty_rencana');
                            
            $detail->sisa_qty = $detail->qty - $qtySudahWO;
        }

        return view('work_order.review_massal', compact('details'));
    }

    /**
     * LANGKAH 2 WO MASSAL: Simpan hasil edit ke Database
     */
 /**
     * LANGKAH 2 WO MASSAL: Simpan hasil edit ke Database
     */
    public function storeMassal(Request $request)
    {
        // Cek apakah ada salah satu pesanan yang belum bayar
        $cekBayar = Pesanan::whereIn('id', $request->pesanan_id)
                            ->where('status_pembayaran', 'Belum Bayar')
                            ->exists();
    
        if ($cekBayar) {
            return redirect()->route('wo.index')->with('error', 'Salah satu pesanan yang dipilih belum membayar DP!');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Buat satu Header WO untuk semua item yang digabung
            $wo = \App\Models\WorkOrder::create([
                'kode_wo'    => 'WO-BATCH-' . strtoupper(bin2hex(random_bytes(3))),
                'tanggal_wo' => now(),
                'status_wo'  => 'Draft',
                'created_by' => auth()->id(),
                'catatan'    => 'Dibuat secara massal/gabungan',
            ]);

            // 2. Looping menggunakan pesanan_id
            foreach ($request->pesanan_id as $index => $p_id) {
                $qtyInput = $request->qty_rencana[$index];

                // Simpan hanya jika jumlahnya lebih dari 0
                if ($qtyInput > 0) {
                    \App\Models\WorkOrderDetail::create([
                        'work_order_id' => $wo->id,
                        'pesanan_id'    => $p_id,
                        'produk_id'     => $request->produk_id[$index],
                        'qty_rencana'   => $qtyInput,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('wo.index')->with('success', 'Work Order Gabungan berhasil dibuat!');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            // JARING PENGAMAN: Jika error, kembalikan ke index, JANGAN pakai back()
            return redirect()->route('wo.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

// app/Http/Controllers/WorkOrderController.php

public function show($id)
{
    $wo = WorkOrder::with([
        'details.pesanan.customer', 
        'details.produk.resep.bahan' // Pastikan relasi ini ada di model
    ])->findOrFail($id);

    return view('work_order.show', compact('wo'));
}

public function kirimProduksi($id)
{
    $wo = WorkOrder::with('details.produk.resep.details.barang')->findOrFail($id);

    DB::beginTransaction();
    try {
        // 1. Buat Header Pengeluaran (Tabel teman Anda)
        $pengeluaran = DB::table('pengeluaran_bahan_baku')->insertGetId([
            'kode_pengeluaran' => 'REQ-WO-' . $wo->kode_wo,
            'tanggal' => now(),
            'gudang_id' => 1, // Sesuaikan dengan ID gudang default atau dari input
            'status' => 'draft',
            'keterangan' => 'Permintaan bahan otomatis dari WO: ' . $wo->kode_wo,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Loop setiap produk di WO untuk mencari kebutuhan bahan di Resep
        foreach ($wo->details as $detail) {
            $resep = $detail->produk->resep;
            
            if ($resep) {
                foreach ($resep->details as $bahan) {
                    // Hitung total kebutuhan: qty rencana WO * qty di resep
                    $totalKebutuhan = $detail->qty_rencana * $bahan->qty;

                    // 3. Masukkan ke Detail Pengeluaran (Tabel teman Anda)
                    DB::table('pengeluaran_bahan_baku_detail')->insert([
                        'pengeluaran_id' => $pengeluaran,
                        'barang_id' => $bahan->barang_id,
                        'qty' => $totalKebutuhan,
                        'satuan' => $bahan->barang->satuan, // Pastikan relasi barang ada
                        'harga_satuan' => $bahan->barang->harga_beli ?? 0,
                        'total_harga' => ($bahan->barang->harga_beli ?? 0) * $totalKebutuhan,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 4. Update status WO menjadi 'Diproses'
        $wo->update(['status_wo' => 'Diproses']);

        DB::commit();
        return redirect()->back()->with('success', 'Permintaan bahan berhasil dikirim ke bagian gudang!');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}
}