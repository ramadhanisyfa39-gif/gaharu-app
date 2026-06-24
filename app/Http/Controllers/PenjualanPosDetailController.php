<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PenjualanPosDetailController extends Controller
{
    public function store(Request $request)
    {
        // BLOKIR AKSI INI
        // Arahkan user untuk mengedit melalui form edit transaksi utama 
        // karena kita menerapkan perhitungan FIFO pada Parent-nya.
        return back()->with('error', 'Penambahan item harus melalui form Edit Transaksi Utama agar stok FIFO dan HPP terpotong dengan benar.');
    }

    public function destroy(string $id)
    {
        // BLOKIR AKSI INI
        return back()->with('error', 'Penghapusan item harus melalui form Edit Transaksi Utama agar stok bahan baku dapat dikembalikan (revert) ke tabel gudang batch.');
    }
}