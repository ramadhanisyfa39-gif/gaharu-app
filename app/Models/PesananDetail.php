<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananDetail extends Model
{
    protected $table = 'pesanan_detail';
    protected $fillable = ['pesanan_id', 'produk_id', 'qty', 'harga'];
    public $timestamps = false;
}
