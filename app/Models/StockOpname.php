<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $table = 'stock_opname';
    protected $fillable = ['tanggal', 'gudang_id', 'created_by', 'keterangan'];
}
