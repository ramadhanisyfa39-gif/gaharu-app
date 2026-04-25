<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $table = 'pembelian';
    protected $fillable = ['kode_pembelian', 'supplier_id', 'tanggal', 'total', 'created_by'];

    public function details()
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
