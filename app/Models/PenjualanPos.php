<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanPos extends Model
{
    protected $table = 'penjualan_pos';
    protected $fillable = ['kode_transaksi', 'tanggal', 'gudang_id', 'total', 'created_by'];
}
