<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemakaianOperasionalDetail extends Model
{
    protected $table = 'pemakaian_operasional_detail';
    protected $fillable = ['pemakaian_id', 'barang_id', 'qty', 'harga_satuan', 'total_biaya'];
    public $timestamps = false;
}
