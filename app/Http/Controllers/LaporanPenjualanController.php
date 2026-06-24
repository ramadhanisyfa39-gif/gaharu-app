<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;

class LaporanPenjualanController extends Controller
{
    public function index(Request $request)
    {
        // 1. Mengatur default filter tanggal
        $tanggal_mulai = $request->get('tanggal_mulai', date('Y-m-01'));
        $tanggal_selesai = $request->get('tanggal_selesai', date('Y-m-t'));

        // 2. Ambil data pesanan beserta total subtotal dari detailnya
        $pesanans = Pesanan::with('customer')
            ->withSum('details', 'subtotal') 
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_selesai])
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Hitung Ringkasan Statistik
        $total_omzet = $pesanans->sum('details_sum_subtotal');
        $total_pesanan = $pesanans->count();
        
        // KODE ANTI-ERROR: Menggunakan filter + strtolower + trim agar kebal dari spasi/kapital
        $pesanan_selesai = $pesanans->filter(function ($p) {
            return strtolower(trim($p->status_pesanan)) === 'selesai';
        })->count();
        
        $pesanan_pending = $pesanans->filter(function ($p) {
            $status = strtolower(trim($p->status_pesanan));
            return $status === 'pending' || $status === 'siap kirim' || $status === 'siap_kirim';
        })->count();

        return view('laporan-penjualan', compact(
            'pesanans', 
            'tanggal_mulai', 
            'tanggal_selesai', 
            'total_omzet', 
            'total_pesanan',
            'pesanan_selesai',
            'pesanan_pending'
        ));
    }
}