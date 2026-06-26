<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiPesanan extends Model
{
    protected $table = 'alokasi_produksi_pesanan';

    protected $fillable = [
        'produksi_id',
        'pesanan_id',
        'produk_id',
        'qty_alokasi',
        'qty_terkirim',
        'hpp_per_unit',
        'total_hpp_alokasi',
    ];

    public function produksi()
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function produk()
    {
        return $this->belongsTo(MasterBarang::class, 'produk_id');
    }
}