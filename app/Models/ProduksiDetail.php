<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiDetail extends Model
{
    protected $table = 'produksi_detail';
    protected $fillable = ['produksi_id', 'produk_id', 'resep_id', 'qty_produksi', 'jumlah_batch', 'total_estimasi_biaya_bahan', 'total_btkl', 'total_bop'];
}
