<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'pesanan_id',
        'tanggal_bayar',
        'jumlah_bayar',
        'metode_pembayaran',
        'catatan',
        'created_by'
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}