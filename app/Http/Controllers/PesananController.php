<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\PesananDetail;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\MasterBarang;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\Pembayaran;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pesanan = Pesanan::with(['customer', 'pembayaran'])->orderBy('created_at', 'desc')->get();

        // Ambil status WO secara realtime untuk dilempar ke sistem UI Blade
        foreach ($pesanan as $p) {
            $woDetail = WorkOrderDetail::where('pesanan_id', $p->id)->first();
            if ($woDetail) {
                $wo = WorkOrder::find($woDetail->work_order_id);
                $p->wo_status = $wo ? strtolower($wo->status_wo) : null;
            } else {
                $p->wo_status = null;
            }
        }

        return view('pesanan.index', compact('pesanan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::all();
        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        return view('pesanan.create', compact('customers', 'produk'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $pesanan = Pesanan::create([
            'kode_pesanan' => $request->kode_pesanan,
            'customer_id' => $request->customer_id,
            'tanggal' => $request->tanggal,
            'estimasi_kirim' => $request->estimasi_kirim,
            'total_pesanan' => $request->total_pesanan,
            'status_pesanan' => 'pending',
            'status_pembayaran' => 'Belum Bayar',
            'created_by' => auth()->id(),
        ]);
    
        foreach ($request->produk_id as $key => $produk) {
            if (!$produk) continue;

            PesananDetail::create([
                'pesanan_id' => $pesanan->id,
                'produk_id' => $produk,
                'qty' => $request->qty[$key],
                'harga' => $request->harga[$key],
                'subtotal' => $request->subtotal[$key],
            ]);
        }

        return redirect()->route('pesanan.index')->with('success', 'Pesanan B2B baru berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pesanan = Pesanan::with(['customer', 'details.produk'])->findOrFail($id);
        return view('pesanan.show', compact('pesanan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pesanan = Pesanan::with('details.produk')->findOrFail($id);

        // PROTEKSI NYATA: Jika sudah terdaftar di WO (baik draft/proses), blokir akses edit via URL
        $sudahWO = WorkOrderDetail::where('pesanan_id', $pesanan->id)->exists();
        if ($sudahWO) {
            return redirect()->route('pesanan.index')
                ->with('error', 'Gagal membuka form! Kontrak pesanan ini sudah diproses ke dalam antrean Work Order.');
        }

        $customers = Customer::all();
        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        return view('pesanan.edit', compact('pesanan', 'customers', 'produk'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pesanan = Pesanan::findOrFail($id);
    
        // update header pesanan
        $pesanan->update([
            'customer_id' => $request->customer_id,
            'tanggal' => $request->tanggal,
            'estimasi_kirim' => $request->estimasi_kirim,
            'total_pesanan' => $request->total_pesanan,
            //'status_pesanan' => $request->status_pesanan,
        ]);
    
        // hapus detail lama
        PesananDetail::where(
            'pesanan_id',
            $pesanan->id
        )->delete();
    
        // simpan ulang detail baru
        foreach ($request->produk_id as $key => $produk) {
    
            if (!$produk) {
                continue;
            }
    
            PesananDetail::create([
                'pesanan_id' => $pesanan->id,
                'produk_id' => $produk,
                'qty' => $request->qty[$key],
                'harga' => $request->harga[$key],
                'subtotal' => $request->subtotal[$key],
            ]);
        }
    
        // ===================================================================
        // TAMBAHKAN LOGIKA OTOMATIS HITUNG ULANG STATUS PEMBAYARAN DI SINI
        // ===================================================================
        // 1. Hitung total uang yang sudah pernah dibayarkan sebelumnya
        $totalBayarSelesai = $pesanan->pembayaran()->sum('jumlah_bayar');
    
        // 2. Bandingkan dengan total_pesanan yang baru setelah di-edit
        if ($totalBayarSelesai >= $pesanan->total_pesanan) {
            // Jika uang yang masuk pas atau lebih, status jadi Lunas
            $pesanan->update(['status_pembayaran' => 'Lunas']);
        } elseif ($totalBayarSelesai > 0) {
            // Jika uang masuk kurang dari total baru tapi sudah pernah bayar, status turun ke DP
            $pesanan->update(['status_pembayaran' => 'DP']);
        } else {
            // Jika memang belum pernah ada pembayaran sama sekali
            $pesanan->update(['status_pembayaran' => 'Belum Bayar']);
        }
        // ===================================================================
    
        return redirect()
            ->route('pesanan.index')
            ->with(
                'success',
                'Pesanan berhasil diupdate dan status keuangan disesuaikan'
            );
    }

    /**
     * Simpan Pembayaran Modal (DP / Lunas)
     */
    public function simpanPembayaran(Request $request, $id)
    {
        $pesanan = Pesanan::findOrFail($id);
        $totalBayarSebelumnya = $pesanan->pembayaran()->sum('jumlah_bayar');
        $sisaTagihan = $pesanan->total_pesanan - $totalBayarSebelumnya;
    
        $minBayar = 1;
        if ($totalBayarSebelumnya == 0) {
            $minBayar = $pesanan->total_pesanan * 0.30;
        }
    
        $request->validate([
            'tanggal_bayar' => 'required|date',
            'jumlah_bayar' => 'required|numeric|min:' . $minBayar . '|max:' . $sisaTagihan,
            'metode_pembayaran' => 'required|string'
        ]);
    
        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'tanggal_bayar' => $request->tanggal_bayar,
            'jumlah_bayar' => $request->jumlah_bayar,
            'metode_pembayaran' => $request->metode_pembayaran,
            'catatan' => $request->catatan,
            'created_by' => auth()->id()
        ]);
    
        $totalBayarBaru = $totalBayarSebelumnya + $request->jumlah_bayar;
    
        if ($totalBayarBaru >= $pesanan->total_pesanan) {
            $pesanan->update(['status_pembayaran' => 'Lunas']);
        } elseif ($totalBayarBaru > 0) {
            $pesanan->update(['status_pembayaran' => 'DP']);
        }
    
        return back()->with('success', 'Catatan kas masuk berhasil divalidasi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pesanan = Pesanan::findOrFail($id);

        // PROTEKSI NYATA: Jika sudah masuk WO, tidak boleh dihapus sama sekali
        $sudahWO = WorkOrderDetail::where('pesanan_id', $pesanan->id)->exists();
        if ($sudahWO) {
            return redirect()->route('pesanan.index')
                ->with('error', 'Data tidak bisa dihapus karena relasi logistik Work Order (WO) sudah terbentuk.');
        }

        PesananDetail::where('pesanan_id', $pesanan->id)->delete();
        $pesanan->delete();

        return redirect()->route('pesanan.index')->with('success', 'Kontrak pesanan berhasil dihapus permanen');
    }
    
    /**
     * Batalkan Pesanan Kontrak
     */
    public function batal($id)
    {
        $pesanan = Pesanan::findOrFail($id);
    
        $woDetail = WorkOrderDetail::where('pesanan_id', $pesanan->id)->first();
        if ($woDetail) {
            $workOrder = WorkOrder::find($woDetail->work_order_id);
    
            // PROTEKSI NYATA: Jika WO berstatus selain draft (misal 'diproses'), gagalkan pembatalan
            if ($workOrder && strtolower($workOrder->status_wo) !== 'draft') {
                return redirect()->route('pesanan.index')
                    ->with('error', 'Pembatalan ditolak! Dapur utama telah memproses bahan baku untuk pesanan ini.');
            }
        }
    
        $pesanan->update(['status_pesanan' => 'dibatalkan']);
    
        return redirect()->route('pesanan.index')->with('success', 'Status kontrak pesanan resmi dibatalkan.');
    }

    /**
     * Kwitansi Cetak
     */
    public function kwitansi($id)
    {
        $pesanan = Pesanan::with(['customer', 'pembayaran'])->findOrFail($id);
        return view('pesanan.kwitansi', compact('pesanan'));
    }
}