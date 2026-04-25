<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiBahanBaku extends Model
{
    protected $table = 'produksi_bahan_baku';
    protected $fillable = ['produksi_detail_id', 'bahan_id', 'qty_estimasi', 'qty_pakai_aktual', 'harga_estimasi', 'harga_satuan_aktual', 'total_estimasi', 'total_biaya_aktual'];
}
