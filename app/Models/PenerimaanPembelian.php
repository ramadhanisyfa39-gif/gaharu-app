<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelian extends Model
{
    use HasFactory;

    protected $table = 'penerimaan_pembelian';

    protected $fillable = [
        'pembelian_id',
        'no_penerimaan',
        'tanggal',
        'created_by'
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function details()
    {
        return $this->hasMany(PenerimaanPembelianDetail::class, 'penerimaan_pembelian_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
