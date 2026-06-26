<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PenjualanPos;
use App\Models\MasterGudang; // Menggunakan model MasterGudang yang benar
use Carbon\Carbon;

class LaporanPenjualanPosController extends Controller
{
    public function index(Request $request)
    {
        // Default filter ke bulan berjalan
        $tanggal_mulai = $request->get('tanggal_mulai', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tanggal_selesai = $request->get('tanggal_selesai', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $gudang_id = $request->get('gudang_id');

        // Mengambil data master gudang langsung dari modelnya untuk dropdown filter
        $gudang = MasterGudang::all();

        // Query mengambil data penjualan pos beserta relasinya
        $query = PenjualanPos::with(['gudang', 'details'])
            ->whereDate('tanggal', '>=', $tanggal_mulai)
            ->whereDate('tanggal', '<=', $tanggal_selesai);

        // Filter berdasarkan gudang jika dipilih
        if ($gudang_id) {
            $query->where('gudang_id', $gudang_id);
        }

        $data_penjualan = $query->orderBy('tanggal', 'desc')->get();

        // Variabel untuk menyimpan akumulasi keuangan
        $total_omzet = 0;
        $total_hpp = 0;

        foreach ($data_penjualan as $item) {
            $total_omzet += $item->total;
            
            // Kalkulasi HPP per transaksi dari relasi detail
            $hpp_transaksi = $item->details ? $item->details->sum(function($d) {
                return $d->hpp_satuan * $d->qty;
            }) : 0;
            
            $total_hpp += $hpp_transaksi;
            
            // Disimpan sementara ke dalam object item untuk dibaca di Blade
            $item->calculated_hpp = $hpp_transaksi; 
            $item->calculated_laba = $item->total - $hpp_transaksi;
        }

        $total_laba = $total_omzet - $total_hpp;

        return view('penjualan_pos.laporan', compact(
            'data_penjualan',
            'gudang',
            'tanggal_mulai',
            'tanggal_selesai',
            'gudang_id',
            'total_omzet',
            'total_hpp',
            'total_laba'
        ));
    }
}