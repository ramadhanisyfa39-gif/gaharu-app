<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanPosDetail extends Model
{
    protected $table = 'penjualan_pos_detail';
    protected $fillable = ['penjualan_id', 'produk_id', 'qty', 'harga', 'hpp_satuan'];
    public $timestamps = false;
}
