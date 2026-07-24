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
        'pembelian_id',
        'penjualan_pos_id',
        'kategori_pembayaran',
        'tanggal_bayar',
        'jumlah_bayar',
        'metode_pembayaran',
        'catatan',
        'bukti_pembayaran',
        'created_by'
    ];

    protected $casts = [
        'bukti_pembayaran' => 'array',
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function penjualanPos()
    {
        return $this->belongsTo(PenjualanPos::class, 'penjualan_pos_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}