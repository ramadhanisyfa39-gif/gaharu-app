<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterBarang;
use App\Models\HargaPeriode;
use Carbon\Carbon; // Wajib ditambahkan untuk memanipulasi dan mengecek tanggal

class HargaBarangPosController extends Controller
{
    // Menampilkan halaman daftar barang (GET)
    public function index()
    {
        $user = auth()->user();
        $queryBarang = \App\Models\MasterBarang::where('is_barang_jadi', 1)->orderBy('nama');

        if ($user && $user->gudang_id) {
            if ($user->gudang_id == 2) {
                $queryBarang->where('tipe_penjualan', 'POS Gaharu');
            } elseif ($user->gudang_id == 4) {
                $queryBarang->where('tipe_penjualan', 'POS Kejingga');
            } else {
                $queryBarang->where('tipe_penjualan', 'POS Gaharu');
            }
        }
        $listBarang = $queryBarang->with(['hargaPosAktif', 'firstFifoLayer'])->get();
    
        return view('harga.index', compact('listBarang'));
    }

    // Menampilkan detail barang, pengaturan harga baru, dan histori harga (GET)
    public function show($id)
    {
        $user = auth()->user();
        $barangTerpilih = \App\Models\MasterBarang::findOrFail($id);

        if ($user && $user->gudang_id) {
            $allowedType = null;
            if ($user->gudang_id == 2) {
                $allowedType = 'POS Gaharu';
            } elseif ($user->gudang_id == 4) {
                $allowedType = 'POS Kejingga';
            }
            if ($allowedType && $barangTerpilih->tipe_penjualan !== $allowedType) {
                abort(403, 'Anda tidak memiliki akses ke produk ini.');
            }
        }

        $riwayatHarga = \App\Models\HargaPeriode::where('barang_id', $id)
            ->orderBy('tgl_mulai', 'desc')
            ->get();
    
        return view('harga.show', compact('barangTerpilih', 'riwayatHarga'));
    }

    // Memproses simpan (POST)
    public function store(Request $request)
    {
        $request->validate([
            'barang_id' => 'required',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'harga_pos' => 'required|numeric',
        ]);

        $isOverlap = HargaPeriode::where('barang_id', $request->barang_id)
            ->where('tgl_mulai', '<=', $request->tgl_selesai)
            ->where('tgl_selesai', '>=', $request->tgl_mulai)
            ->exists();

        if ($isOverlap) {
            return redirect()->back()->withInput()->withErrors([
                'tgl_mulai' => 'Gagal! Rentang tanggal ini beririsan dengan periode harga yang sudah ada.'
            ]);
        }

        HargaPeriode::create($request->all());

        return redirect()->back()->with('success', 'Harga berhasil disimpan!');
    }

    
    // Memproses Update (PUT/PATCH)
    public function update(Request $request, $id)
    {
        $harga = HargaPeriode::findOrFail($id);
        $hariIni = Carbon::today()->format('Y-m-d');
    
        // KONDISI 1: Harga Masa Lalu (Tolak Update)
        if ($harga->tgl_selesai < $hariIni) {
            return redirect()->back()->withErrors(['error' => 'Data harga masa lalu tidak bisa diubah demi riwayat laporan.']);
        }
    
        // KONDISI 2: Harga Sedang Aktif (Ubah tgl_selesai untuk mengakhiri periode)
        if ($harga->tgl_mulai <= $hariIni && $harga->tgl_selesai >= $hariIni) {
            $request->validate([
                // REVISI: Validasi diubah ke 'tgl_mulai' asli data tersebut, BUKAN ke 'hari ini'.
                // Ini agar jika diakhiri per tanggal kemarin (H-1), tidak dicegat oleh validasi.
                'tgl_selesai' => 'required|date|after_or_equal:' . $harga->tgl_mulai,
            ]);
    
            // Proteksi: Hanya update field tgl_selesai saja
            $harga->update([
                'tgl_selesai' => $request->tgl_selesai
            ]);
            
            return redirect()->back()->with('success', 'Periode harga berhasil diakhiri / diperbarui.');
        }
    
        // KONDISI 3: Harga Masa Depan (Boleh ubah semua)
        if ($harga->tgl_mulai > $hariIni) {
            $request->validate([
                'tgl_mulai' => 'required|date',
                'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
                'harga_pos' => 'required|numeric',
            ]);
    
            // Cek overlap lagi, tapi abaikan ID dirinya sendiri
            $isOverlap = HargaPeriode::where('barang_id', $harga->barang_id)
                ->where('id', '!=', $id) 
                ->where('tgl_mulai', '<=', $request->tgl_selesai)
                ->where('tgl_selesai', '>=', $request->tgl_mulai)
                ->exists();
    
            if ($isOverlap) {
                return redirect()->back()->withErrors(['error' => 'Rentang tanggal baru beririsan dengan periode lain.']);
            }
    
            $harga->update($request->all());
            return redirect()->back()->with('success', 'Data harga masa depan berhasil diubah!');
        }
    }

    // Memproses Hapus (DELETE)
    public function destroy($id)
    {
        $harga = HargaPeriode::findOrFail($id);
        $hariIni = Carbon::today()->format('Y-m-d');

        // HANYA BOLEH hapus jika harga baru berlaku di masa depan
        if ($harga->tgl_mulai > $hariIni) {
            $harga->delete();
            return redirect()->back()->with('success', 'Harga masa depan berhasil dihapus.');
        }

        // Tolak hapus jika sedang aktif atau masa lalu
        return redirect()->back()->withErrors(['error' => 'Gagal! Hanya harga di masa depan yang boleh dihapus untuk menjaga riwayat transaksi.']);
    }
}