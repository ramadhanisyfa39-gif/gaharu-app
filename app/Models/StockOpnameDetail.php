<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = 'stock_opname_detail';

    protected $fillable = [
        'stock_opname_id',
        'barang_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'nilai_selisih',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function stockOpname()
    {
        return $this->belongsTo(
            StockOpname::class,
            'stock_opname_id'
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            MasterBarang::class,
            'barang_id'
        );
    }
}