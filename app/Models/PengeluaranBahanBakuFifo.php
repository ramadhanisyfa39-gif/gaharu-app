<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengeluaranBahanBakuFifo extends Model
{
    protected $table =
        'pengeluaran_bahan_baku_fifo';

    protected $fillable = [

        'pengeluaran_id',

        'detail_id',

        'batch_id',

        'batch_number',

        'qty_keluar',

        'harga_per_qty',

        'total_harga',
    ];
}