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

        /*
        |--------------------------------------------------------------------------
        | AMBIL SEMUA BARANG MASTER
        |--------------------------------------------------------------------------
        |
        | Barang tetap tampil walaupun:
        | - belum pernah dibeli
        | - stok = 0
        |
        */

        $query = MasterBarang::query()

            ->leftJoin('stok_gudang', function ($join) use ($gudangId) {

                $join->on(
                    'master_barang.id',
                    '=',
                    'stok_gudang.barang_id'
                );

                if ($gudangId) {

                    $join->where(
                        'stok_gudang.gudang_id',
                        $gudangId
                    );
                }
            })

            ->leftJoin(
                'master_gudang',
                'stok_gudang.gudang_id',
                '=',
                'master_gudang.id'
            )

            ->select([
                'master_barang.id',
                'master_barang.kode_barang',
                'master_barang.nama',
                'master_barang.satuan',

                DB::raw("
                    COALESCE(master_gudang.nama,'-')
                    as nama_gudang
                "),

                DB::raw("
                    COALESCE(stok_gudang.jumlah,0)
                    as qty
                ")
            ]);

        /*
        |--------------------------------------------------------------------------
        | FILTER BARANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('barang_id')) {

            $query->where(
                'master_barang.id',
                $request->barang_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER GUDANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('gudang_id')) {

            $query->where(function ($q) use ($gudangId) {

                $q->where(
                    'stok_gudang.gudang_id',
                    $gudangId
                )
                ->orWhereNull(
                    'stok_gudang.gudang_id'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $stokGudang = $query
            ->orderBy('master_barang.nama')
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | MASTER FILTER
        |--------------------------------------------------------------------------
        */

        $gudangs = MasterGudang::orderBy('nama')->get();

        $barangs = MasterBarang::orderBy('nama')->get();

        return view(
            'stok-gudang.index',
            compact(
                'stokGudang',
                'gudangs',
                'barangs'
            )
        );
    }
}