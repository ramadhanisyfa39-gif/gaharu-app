<?php

namespace App\Http\Controllers;

use App\Models\PenjualanPosDetail;
use Illuminate\Http\Request;

class PenjualanPosDetailController extends Controller
{
    public function store(Request $request)
    {
        // BLOKIR AKSI INI
        // Arahkan user untuk mengedit melalui form edit transaksi utama
        return back()->with('error', 'Penambahan item harus melalui form Edit Transaksi Utama agar stok FIFO terpotong dengan benar.');
    }

    public function destroy(string $id)
    {
        // BLOKIR AKSI INI
        return back()->with('error', 'Penghapusan item harus melalui form Edit Transaksi Utama agar stok bahan baku dapat dikembalikan (revert) ke gudang.');
    }
}