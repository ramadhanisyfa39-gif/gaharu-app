<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    protected $table = 'pengiriman'; // Tanpa "s"
    
    protected $fillable = [
        'no_pengiriman', 
        'pesanan_id', 
        'tanggal_pengiriman', 
        'kurir'
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