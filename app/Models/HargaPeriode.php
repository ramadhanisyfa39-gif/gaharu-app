<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaPeriode extends Model
{
    use HasFactory;

    // 1. Beritahu Laravel nama tabel aslinya di database
    protected $table = 'harga_barang_pos';

    // 2. Tentukan kolom mana saja yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'barang_id',
        'tgl_mulai',
        'tgl_selesai',
        'harga_pos',
        'keterangan',
    ];

    /**
     * Relasi Balik ke MasterBarang
     * Setiap record harga periode ini dimiliki oleh satu barang
     */
    public function barang()
    {
        // 'barang_id' adalah foreign key di tabel ini
        // 'id' adalah primary key di tabel master_barang
        return $this->belongsTo(MasterBarang::class, 'barang_id', 'id');
    }
}