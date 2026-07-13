<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\Produksi;
use App\Models\ChartOfAccount;

class LaporanProduksiController extends Controller
{
    /**
     * 1. LAPORAN REKAPITULASI PRODUKSI (OPERASIONAL)
     */
    public function rekapitulasi(Request $request)
    {
        // Set default filter bulan berjalan (awal bulan s/d akhir bulan)
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate   = $request->get('end_date', date('Y-m-t'));

        $rekapitulasi = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->leftJoin('master_barang', 'produksi_detail.produk_id', '=', 'master_barang.id')
            ->leftJoin('master_gudang', 'produksi.gudang_hasil_id', '=', 'master_gudang.id')
            ->select(
                'produksi.tanggal_mulai as tanggal',
                'produksi.kode_produksi',
                'master_barang.nama as nama_produk',
                'master_gudang.nama as nama_gudang',
                'produksi_detail.qty as qty_hasil',
                'produksi.status_produksi',
                // Subquery Kode WO
                DB::raw('(SELECT wo.kode_wo 
                          FROM work_order wo 
                          JOIN work_order_detail wod ON wod.work_order_id = wo.id 
                          WHERE wod.pesanan_id = produksi.pesanan_id 
                          LIMIT 1) as kode_wo'),
                // Subquery Target Qty Rencana dari WO
                DB::raw('(SELECT SUM(wod.qty_rencana) 
                          FROM work_order wo 
                          JOIN work_order_detail wod ON wod.work_order_id = wo.id 
                          WHERE wod.pesanan_id = produksi.pesanan_id 
                          AND wod.produk_id = produksi_detail.produk_id 
                          LIMIT 1) as qty_target')
            )
            ->whereBetween('produksi.tanggal_mulai', [$startDate, $endDate])
            ->orderBy('produksi.tanggal_mulai', 'desc')
            ->get();

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('laporanproduksi.rekapitulasi-pdf', compact(
                'rekapitulasi', 'startDate', 'endDate'
            ));
            return $pdf->download('laporan-rekapitulasi-produksi-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelRekapitulasi($rekapitulasi);
        }

        return view('laporanproduksi.rekapitulasi', compact('rekapitulasi', 'startDate', 'endDate'));
    }

    private function exportExcelRekapitulasi($data)
    {
        $filename = 'laporan-rekapitulasi-produksi-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['Tanggal', 'Kode Produksi', 'Kode WO', 'Nama Produk', 'Gudang Tujuan', 'Target WO', 'Realisasi Output', 'Status']);
            foreach ($data as $row) {
                fputcsv($f, [
                    \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y'),
                    $row->kode_produksi,
                    $row->kode_wo ?? '-',
                    $row->nama_produk,
                    $row->nama_gudang ?? 'Gudang B2B',
                    $row->qty_target,
                    $row->qty_hasil,
                    strtoupper($row->status_produksi ?? 'SELESAI'),
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 2. LAPORAN HARGA POKOK PRODUKSI / HPP (AKUNTANSI)
     */
    public function hpp(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate   = $request->get('end_date', date('Y-m-t'));

        // Query summary HPP dikelompokkan per Produk
        $laporanHpp = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->leftJoin('master_barang', 'produksi_detail.produk_id', '=', 'master_barang.id')
            ->select(
                'master_barang.kode_barang',
                'master_barang.nama as nama_produk',
                'master_barang.satuan', // Mengambil satuan barang (Gr, Cup, Kg, dll)
                DB::raw('SUM(produksi_detail.qty) as total_qty'),
                DB::raw('SUM(produksi_detail.hpp_total) as total_hpp')
            )
            ->whereBetween('produksi.tanggal_mulai', [$startDate, $endDate])
            ->groupBy('master_barang.id', 'master_barang.kode_barang', 'master_barang.nama', 'master_barang.satuan')
            ->orderBy('total_hpp', 'desc')
            ->get();

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('laporanproduksi.hpp-pdf', compact(
                'laporanHpp', 'startDate', 'endDate'
            ));
            return $pdf->download('laporan-hpp-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelHpp($laporanHpp);
        }

        return view('laporanproduksi.hpp', compact('laporanHpp', 'startDate', 'endDate'));
    }

    private function exportExcelHpp($data)
    {
        $filename = 'laporan-hpp-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['Kode Barang', 'Nama Produk Jadi', 'Total Qty Produksi', 'Satuan', 'Total Nilai HPP', 'Rata-rata HPP / Satuan']);
            foreach ($data as $row) {
                $hppPerSatuan = $row->total_qty > 0 ? ($row->total_hpp / $row->total_qty) : 0;
                fputcsv($f, [
                    $row->kode_barang,
                    $row->nama_produk,
                    $row->total_qty,
                    $row->satuan ?? 'Pcs',
                    $row->total_hpp,
                    $hppPerSatuan,
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 3. DASHBOARD PRODUKSI (REPORTS)
     */
    public function dashboard(Request $request)
    {
        // 1. Mini Summary Cards
        $woAktif = WorkOrder::where('status_wo', 'Diproses')->count();
        
        $produksiSelesaiTahunIni = Produksi::where('status_produksi', 'Selesai')
            ->whereYear('tanggal_selesai', date('Y'))
            ->count();

        $totalQtyHasil = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->where('produksi.status_produksi', 'Selesai')
            ->sum('produksi_detail.qty');

        // Target Achievement Calculation
        $workOrders = WorkOrder::whereIn('status_wo', ['Draft', 'Diproses', 'Selesai'])->get();
        $achievements = [];

        foreach ($workOrders as $wo) {
            $totalRencana = $wo->details()->sum('qty_rencana');
            
            $pesananIds = $wo->details()->pluck('pesanan_id')->filter()->unique()->toArray();
            $produkIds = $wo->details()->pluck('produk_id')->filter()->unique()->toArray();
            
            $totalAlokasi = 0;
            if (!empty($pesananIds) && !empty($produkIds)) {
                $totalAlokasi = DB::table('alokasi_produksi_pesanan')
                    ->whereIn('pesanan_id', $pesananIds)
                    ->whereIn('produk_id', $produkIds)
                    ->sum('qty_alokasi');
            }
            
            if ($totalRencana > 0) {
                $achievements[] = min(100, ($totalAlokasi / $totalRencana) * 100);
            }
        }

        $rataRataCapaian = count($achievements) > 0 ? (array_sum($achievements) / count($achievements)) : 0;

        // 2. Grafik Tren Produksi 7 Hari Terakhir
        $labelsProduksi = [];
        $dataProduksi = [];

        $chartData = DB::table('produksi')
            ->join('produksi_detail', 'produksi.id', '=', 'produksi_detail.produksi_id')
            ->where('produksi.status_produksi', 'Selesai')
            ->where('produksi.tanggal_selesai', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(produksi.tanggal_selesai) as date_label, SUM(produksi_detail.qty) as daily_qty')
            ->groupBy('date_label')
            ->get()
            ->pluck('daily_qty', 'date_label');

        $periode = \Carbon\CarbonPeriod::create(
            now()->subDays(6),
            now()
        );

        foreach ($periode as $tanggal) {
            $dateStr = $tanggal->format('Y-m-d');
            $labelsProduksi[] = $tanggal->format('d M');
            $dataProduksi[] = (float) ($chartData->get($dateStr) ?? 0);
        }

        // 3. List Bahan Baku yang Sudah Masuk ke Batas Minimum
        $bahanBakuMinimum = DB::table('master_barang')
            ->leftJoin('stok_gudang', 'master_barang.id', '=', 'stok_gudang.barang_id')
            ->where('master_barang.is_bahan_baku', 1)
            ->select(
                'master_barang.nama',
                'master_barang.satuan',
                'master_barang.minimum_stock',
                DB::raw('COALESCE(SUM(stok_gudang.jumlah), 0) as total_stok')
            )
            ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan', 'master_barang.minimum_stock')
            ->havingRaw('total_stok <= master_barang.minimum_stock')
            ->get();

        // 4. Produk Teratas Diproduksi (Top 5)
        $produkTeratas = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->join('master_barang', 'produksi_detail.produk_id', '=', 'master_barang.id')
            ->where('produksi.status_produksi', 'Selesai')
            ->select('master_barang.nama', 'master_barang.satuan', DB::raw('SUM(produksi_detail.qty) as total_qty'))
            ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 5. Status Work Order
        $workOrderStatusQuery = WorkOrder::with('pembuat')->latest();
        
        // If exporting, get all instead of limit 5
        if ($request->format === 'pdf' || $request->format === 'excel') {
            $workOrderStatus = $workOrderStatusQuery->get();
        } else {
            $workOrderStatus = $workOrderStatusQuery->limit(5)->get();
        }

        $workOrderStatus = $workOrderStatus->map(function ($wo) {
            $totalRencana = $wo->details()->sum('qty_rencana');
            
            $pesananIds = $wo->details()->pluck('pesanan_id')->filter()->unique()->toArray();
            $produkIds = $wo->details()->pluck('produk_id')->filter()->unique()->toArray();
            
            $totalAlokasi = 0;
            if (!empty($pesananIds) && !empty($produkIds)) {
                $totalAlokasi = DB::table('alokasi_produksi_pesanan')
                    ->whereIn('pesanan_id', $pesananIds)
                    ->whereIn('produk_id', $produkIds)
                    ->sum('qty_alokasi');
            }
            
            $wo->total_rencana = $totalRencana;
            $wo->total_realisasi = $totalAlokasi;
            $wo->persentase = $totalRencana > 0 ? round(($totalAlokasi / $totalRencana) * 100, 2) : 0;
            return $wo;
        });

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('laporanproduksi.dashboard-pdf', compact('workOrderStatus'));
            return $pdf->download('laporan-work-order-status-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelWO($workOrderStatus);
        }

        return view('laporanproduksi.dashboard', compact(
            'woAktif',
            'produksiSelesaiTahunIni',
            'totalQtyHasil',
            'rataRataCapaian',
            'labelsProduksi',
            'dataProduksi',
            'bahanBakuMinimum',
            'produkTeratas',
            'workOrderStatus'
        ));
    }

    private function exportExcelWO($data)
    {
        $filename = 'laporan-work-order-status-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['Kode WO', 'Tanggal WO', 'Pembuat', 'Total Rencana Qty', 'Total Realisasi Qty', 'Realisasi %', 'Status']);
            foreach ($data as $row) {
                fputcsv($f, [
                    $row->kode_wo,
                    \Carbon\Carbon::parse($row->tanggal_wo)->format('d-m-Y'),
                    $row->pembuat->nama ?? 'Sistem',
                    $row->total_rencana,
                    $row->total_realisasi,
                    $row->persentase . '%',
                    $row->status_wo,
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}