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

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('penjualan_pos.laporan-pdf', compact(
                'data_penjualan', 'tanggal_mulai', 'tanggal_selesai',
                'total_omzet', 'total_hpp', 'total_laba'
            ));
            return $pdf->download('laporan-penjualan-pos-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcel($data_penjualan);
        }

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

    private function exportExcel($data)
    {
        $filename = 'laporan-penjualan-pos-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['No', 'No. Transaksi', 'Tanggal & Waktu', 'Gudang / Outlet', 'Total Omzet', 'Total HPP', 'Laba Kotor']);
            foreach ($data as $index => $row) {
                fputcsv($f, [
                    $index + 1,
                    $row->kode_transaksi,
                    Carbon::parse($row->tanggal)->format('d-m-Y H:i'),
                    $row->gudang->nama ?? '-',
                    $row->total,
                    $row->calculated_hpp,
                    $row->calculated_laba,
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}