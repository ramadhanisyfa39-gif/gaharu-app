<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\PesananDetail;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\MasterBarang;
use App\Models\WorkOrderDetail;
use App\Models\Pembayaran;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pesanan = Pesanan::with('customer')->get();

        return view('pesanan.index', compact('pesanan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::all();

        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        return view('pesanan.create', compact(
            'customers',
            'produk'
        ));
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
            'status_pembayaran' => 'Belum Bayar', // Pastikan defaultnya ini
            'created_by' => auth()->id(),
        ]);
    
        // simpan detail pesanan
        foreach ($request->produk_id as $key => $produk) {

            // skip kalau produk kosong
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

        return redirect()
            ->route('pesanan.index')
            ->with(
                'success',
                'Pesanan berhasil ditambahkan'
            );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pesanan = Pesanan::with([
            'customer',
            'details.produk'
        ])->findOrFail($id);

        return view(
            'pesanan.show',
            compact('pesanan')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pesanan = Pesanan::with(
            'details.produk'
        )->findOrFail($id);

        // cek apakah pesanan sudah masuk WO
        $sudahWO = WorkOrderDetail::where(
            'pesanan_id',
            $pesanan->id
        )->exists();

        if ($sudahWO) {

            return redirect()
                ->route('pesanan.index')
                ->with(
                    'error',
                    'Pesanan tidak bisa diedit karena sudah masuk Work Order'
                );
        }

        $customers = Customer::all();

        $produk = MasterBarang::where(
            'is_barang_jadi',
            1
        )->get();

        return view(
            'pesanan.edit',
            compact(
                'pesanan',
                'customers',
                'produk'
            )
        );
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
    
            'status_pesanan' => $request->status_pesanan,
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
    
        return redirect()
            ->route('pesanan.index')
            ->with(
                'success',
                'Pesanan berhasil diupdate'
            );
    }

    public function simpanPembayaran(Request $request, $id)
    {
        $pesanan = Pesanan::findOrFail($id);
    
        // Hitung uang yang sudah masuk sebelumnya
        $totalBayarSebelumnya = $pesanan->pembayaran()->sum('jumlah_bayar');
        
        // Hitung sisa tagihan
        $sisaTagihan = $pesanan->total_pesanan - $totalBayarSebelumnya;
    
        // Tentukan minimal pembayaran (30% jika belum bayar, Rp 1 jika sudah DP)
        $minBayar = 1;
        if ($totalBayarSebelumnya == 0) {
            $minBayar = $pesanan->total_pesanan * 0.30;
        }
    
        // Tambahkan validasi max:$sisaTagihan
        $request->validate([
            'tanggal_bayar' => 'required|date',
            'jumlah_bayar' => 'required|numeric|min:' . $minBayar . '|max:' . $sisaTagihan,
            'metode_pembayaran' => 'required|string'
        ], [
            'jumlah_bayar.min' => 'Pembayaran minimal adalah Rp ' . number_format($minBayar, 0, ',', '.') . '.',
            'jumlah_bayar.max' => 'Pembayaran ditolak! Jumlah bayar tidak boleh melebihi sisa tagihan (Maks: Rp ' . number_format($sisaTagihan, 0, ',', '.') . ').'
        ]);
    
        // 1. Simpan data ke tabel pembayaran
        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'tanggal_bayar' => $request->tanggal_bayar,
            'jumlah_bayar' => $request->jumlah_bayar,
            'metode_pembayaran' => $request->metode_pembayaran,
            'catatan' => $request->catatan,
            'created_by' => auth()->id()
        ]);
    
        // 2. Hitung total uang yang sudah masuk SETELAH pembayaran ini
        $totalBayarBaru = $totalBayarSebelumnya + $request->jumlah_bayar;
    
        // 3. Update status pembayaran di tabel Pesanan
        if ($totalBayarBaru >= $pesanan->total_pesanan) {
            $pesanan->update(['status_pembayaran' => 'Lunas']);
        } elseif ($totalBayarBaru > 0) {
            $pesanan->update(['status_pembayaran' => 'DP']);
        }
    
        return back()->with('success', 'Pembayaran berhasil disimpan. Status pesanan diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pesanan = Pesanan::findOrFail($id);

        // cek apakah sudah masuk WO
        $sudahWO = WorkOrderDetail::where(
            'pesanan_id',
            $pesanan->id
        )->exists();

        if ($sudahWO) {

            return redirect()
                ->route('pesanan.index')
                ->with(
                    'error',
                    'Pesanan tidak bisa dihapus karena sudah masuk Work Order'
                );
        }

        // hapus detail dulu
        PesananDetail::where(
            'pesanan_id',
            $pesanan->id
        )->delete();

        // hapus header
        $pesanan->delete();

        return redirect()
            ->route('pesanan.index')
            ->with(
                'success',
                'Pesanan berhasil dihapus'
            );
    }
    public function kwitansi($id)
{
    // Ambil data pesanan beserta customer dan riwayat pembayarannya
    $pesanan = Pesanan::with(['customer', 'pembayaran'])->findOrFail($id);
    
    return view('pesanan.kwitansi', compact('pesanan'));
}
}