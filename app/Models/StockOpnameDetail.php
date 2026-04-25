<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = 'stock_opname_detail';
    protected $fillable = ['opname_id', 'barang_id', 'stok_sistem', 'stok_fisik', 'selisih', 'tipe'];
    public $timestamps = false;
}
