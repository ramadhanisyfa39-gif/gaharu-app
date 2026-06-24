<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('laporanproduksi.rekapitulasi', compact('rekapitulasi', 'startDate', 'endDate'));
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
            ->groupBy('master_barang.kode_barang', 'master_barang.nama', 'master_barang.satuan')
            ->orderBy('total_hpp', 'desc')
            ->get();

        return view('laporanproduksi.hpp', compact('laporanHpp', 'startDate', 'endDate'));
    }
}