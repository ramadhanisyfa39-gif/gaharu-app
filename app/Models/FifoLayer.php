<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FifoLayer extends Model
{
    protected $table = 'fifo_layers';
    public $timestamps = false;
    protected $fillable = [
        'barang_id',
        'gudang_id',
        'qty_masuk',
        'qty_sisa',
        'harga_per_unit',
        'batch_number',
        'tanggal_masuk',
        'referensi_transaksi'
    ];
}
