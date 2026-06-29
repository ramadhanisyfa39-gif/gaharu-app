<?php

namespace App\Http\Controllers;

use App\Models\MasterGudang;
use App\Models\MasterBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokGudangController extends Controller
{
    public function index(Request $request)
    {
        $gudangId = $request->gudang_id;
        $barangId = $request->barang_id;

        /*
        |--------------------------------------------------------------------------
        | QUERY UTAMA
        |--------------------------------------------------------------------------
        |
        | Tampilkan semua kombinasi barang × gudang yang punya record stok_gudang.
        | Jika filter gudang aktif → hanya gudang itu.
        | Jika filter barang aktif → hanya barang itu.
        | Barang yang belum punya stok_gudang sama sekali TETAP muncul
        | (lewat subquery UNION di bawah) agar inventory lengkap.
        |
        */

        // ── Bagian 1: Barang yang sudah punya baris di stok_gudang ──
        $query = DB::table('stok_gudang')
            ->join('master_barang', 'master_barang.id', '=', 'stok_gudang.barang_id')
            ->join('master_gudang',  'master_gudang.id',  '=', 'stok_gudang.gudang_id')
            ->select([
                'master_barang.id',
                'master_barang.kode_barang',
                'master_barang.nama',
                'master_barang.satuan',
                'master_gudang.nama   as nama_gudang',
                'stok_gudang.gudang_id',
                'stok_gudang.jumlah   as qty',
            ]);

        if ($gudangId) {
            $query->where('stok_gudang.gudang_id', $gudangId);
        }

        if ($barangId) {
            $query->where('master_barang.id', $barangId);
        }

        // Ambil semua hasil (kita paginate manual di bawah)
        $rows = $query->orderBy('master_barang.nama')->orderBy('master_gudang.nama')->get();

        /*
        |--------------------------------------------------------------------------
        | HITUNG STATUS & NILAI FIFO PER BARIS
        |--------------------------------------------------------------------------
        */

        $rows = $rows->map(function ($row) {
            // Status stok
            $row->status = $row->qty > 0 ? 'tersedia' : 'habis';

            // Nilai FIFO: ambil dari batch yang masih punya sisa
            $hargaFifo = DB::table('stok_gudang_batch')
                ->where('gudang_id', $row->gudang_id)
                ->where('barang_id', $row->id)
                ->where('qty_sisa', '>', 0)
                ->avg('harga_per_qty');

            // Fallback ke rata-rata semua batch historis
            if (!$hargaFifo) {
                $hargaFifo = DB::table('stok_gudang_batch')
                    ->where('gudang_id', $row->gudang_id)
                    ->where('barang_id', $row->id)
                    ->avg('harga_per_qty');
            }

            // Fallback terakhir ke hpp_referensi master_barang
            if (!$hargaFifo) {
                $hargaFifo = DB::table('master_barang')
                    ->where('id', $row->id)
                    ->value('hpp_referensi') ?? 0;
            }

            $row->harga_fifo  = $hargaFifo ?? 0;
            $row->nilai_stok  = $row->qty * $row->harga_fifo;

            return $row;
        });

        /*
        |--------------------------------------------------------------------------
        | PAGINATE MANUAL
        |--------------------------------------------------------------------------
        */

        $perPage     = 20;
        $currentPage = (int) ($request->page ?? 1);
        $total       = $rows->count();
        $items       = $rows->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $stokGudang = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        /*
        |--------------------------------------------------------------------------
        | FILTER OPTIONS
        |--------------------------------------------------------------------------
        */

        $gudangs = MasterGudang::orderBy('nama')->get();
        $barangs = MasterBarang::orderBy('nama')->get();

        return view(
            'stok-gudang.index',
            compact('stokGudang', 'gudangs', 'barangs')
        );
    }
}