<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    protected $table = 'produksi';
    protected $fillable = ['kode_produksi', 'tanggal_mulai', 'tanggal_selesai', 'status_produksi', 'gudang_bahan_id', 'gudang_hasil_id', 'created_by'];

    public function details()
    {
        return $this->hasMany(ProduksiDetail::class, 'produksi_id');
    }
}
