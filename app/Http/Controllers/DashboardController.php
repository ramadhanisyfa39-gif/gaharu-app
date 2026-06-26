<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Supplier;
use App\Models\MasterBarang;
use App\Models\WorkOrder;
use App\Models\Pembelian;
use App\Models\StokGudang;
use App\Models\StokGudangBatch;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | SUMMARY CARD (Abaikan jika tidak dipakai di view)
        |--------------------------------------------------------------------------
        */
        $totalPesanan = Pesanan::count();
        $totalWO = WorkOrder::count();
        $totalSupplier = Supplier::count();
        $totalProduk = MasterBarang::count();

        /*
        |--------------------------------------------------------------------------
        | NILAI INVENTORY FIFO
        |--------------------------------------------------------------------------
        */
        $inventoryValue = StokGudangBatch::select(
            DB::raw('SUM(qty_sisa * harga_per_qty) as total')
        )->value('total');

        $inventoryValue = $inventoryValue ?? 0;

        /*
        |--------------------------------------------------------------------------
        | PEMBELIAN BULAN INI
        |--------------------------------------------------------------------------
        */
        $pembelianBulanIni = Pembelian::whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->sum('total');

        /*
        |--------------------------------------------------------------------------
        | BARANG HAMPIR HABIS
        |--------------------------------------------------------------------------
        */
        $barangHampirHabis = StokGudang::query()
            ->join('master_barang', 'stok_gudang.barang_id', '=', 'master_barang.id')
            ->where('master_barang.is_active', true) // <--- TAMBAHKAN BARIS INI
            ->where('stok_gudang.jumlah', '<=', 10)
            ->select(
                'master_barang.nama',
                'master_barang.satuan',
                'stok_gudang.jumlah'
            )
            ->orderBy('stok_gudang.jumlah')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | GRAFIK PEMBELIAN 7 HARI TERAKHIR
        |--------------------------------------------------------------------------
        */
        $labelsPembelian = [];
        $dataPembelian = [];

        $periode = CarbonPeriod::create(
            now()->subDays(6),
            now()
        );

        foreach ($periode as $tanggal) {
            $labelsPembelian[] = $tanggal->format('d M');

            $dataPembelian[] = Pembelian::whereDate('tanggal', $tanggal->format('Y-m-d'))
                ->sum('total');
        }

        /*
        |--------------------------------------------------------------------------
        | REVISI: 3 BAHAN DENGAN QUANTITY PEMBELIAN TERBANYAK
        |--------------------------------------------------------------------------
        */
        try {
            $bahanSeringDibeli = DB::table('pembelian_detail')
                ->join('master_barang', 'pembelian_detail.barang_id', '=', 'master_barang.id')
                ->select('master_barang.nama', 'master_barang.satuan', DB::raw('SUM(pembelian_detail.qty) as total_qty'))
                ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan')
                ->orderByDesc('total_qty')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            try {
                $bahanSeringDibeli = DB::table('pembelian_details')
                    ->join('master_barang', 'pembelian_details.barang_id', '=', 'master_barang.id')
                    ->select('master_barang.nama', 'master_barang.satuan', DB::raw('SUM(pembelian_details.qty) as total_qty'))
                    ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan')
                    ->orderByDesc('total_qty')
                    ->limit(3)
                    ->get();
            } catch (\Exception $ex) {
                $bahanSeringDibeli = collect();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TAMBAHAN: 3 SUPPLIER TERATAS
        |--------------------------------------------------------------------------
        */
        try {
            $supplierTeratas = DB::table('pembelian')
                ->join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
                ->select('supplier.nama', DB::raw('SUM(pembelian.total) as total_nominal'))
                ->groupBy('supplier.id', 'supplier.nama')
                ->orderByDesc('total_nominal')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            try {
                $supplierTeratas = DB::table('pembelian')
                    ->join('suppliers', 'pembelian.supplier_id', '=', 'suppliers.id')
                    ->select('suppliers.nama', DB::raw('SUM(pembelian.total) as total_nominal'))
                    ->groupBy('suppliers.id', 'suppliers.nama')
                    ->orderByDesc('total_nominal')
                    ->limit(3)
                    ->get();
            } catch (\Exception $ex) {
                $supplierTeratas = collect();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | KEMBALIKAN KE VIEW
        |--------------------------------------------------------------------------
        */
        return view('dashboard', compact(
            'totalPesanan', 
            'totalWO', 
            'totalSupplier', 
            'totalProduk',
            'inventoryValue', 
            'pembelianBulanIni', 
            'barangHampirHabis',
            'labelsPembelian', 
            'dataPembelian',
            'bahanSeringDibeli',
            'supplierTeratas'
        ));
    }
}