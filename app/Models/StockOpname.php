<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $table = 'stock_opname';

    protected $fillable = [
        'kode_opname',
        'tanggal',
        'gudang_id',
        'status',
        'keterangan',
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function gudang()
    {
        return $this->belongsTo(
            MasterGudang::class,
            'gudang_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function details()
    {
        return $this->hasMany(
            StockOpnameDetail::class,
            'stock_opname_id'
        );
    }
    public function loadBarang(Request $request)
    {
        $barang = DB::table('stok_gudang')
            ->join(
                'master_barang',
                'stok_gudang.barang_id',
                '=',
                'master_barang.id'
            )
            ->where(
                'stok_gudang.gudang_id',
                $request->gudang_id
            )
            ->select(
                'master_barang.id',
                'master_barang.kode_barang',
                'master_barang.nama',
                'master_barang.satuan',
                DB::raw('SUM(stok_gudang.jumlah) as stok')
            )
            ->groupBy(
                'master_barang.id',
                'master_barang.kode_barang',
                'master_barang.nama',
                'master_barang.satuan'
            )
            ->orderBy('master_barang.nama')
            ->get();

        return response()->json($barang);
    }
}