<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    protected $table = 'pengiriman'; 
    
    protected $fillable = [
        'no_pengiriman', 
        'pesanan_id', 
        'tanggal_pengiriman', 
        'kurir',
        'status_pengiriman' // <-- Ditambahkan
    ];

    public function details()
    {
        return $this->hasMany(PengirimanDetail::class, 'pengiriman_id');
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
}