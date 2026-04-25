<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'Pembayaran';
    protected $fillable = ['pesanan_id', 'tanggal_pembayaran', 'metode_pembayaran', 'jenis_pembayaran', 'jumlah_bayar'];
}
