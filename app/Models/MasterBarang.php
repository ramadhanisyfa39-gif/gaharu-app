<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBarang extends Model
{
    protected $table = 'master_barang';
    protected $fillable = [
        'kategori_id',
        'kode_barang',
        'nama',
        'satuan',
        'is_bahan_baku',
        'is_barang_jadi',
        'is_operational',
        'is_direct_consumption',
        'harga_jual_b2b',
        'harga_jual_pos',
        'hpp_referensi'
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
}
