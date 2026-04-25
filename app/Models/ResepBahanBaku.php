<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepBahanBaku extends Model
{
    protected $table = 'ResepBahanBaku';
    protected $fillable = ['resep_id', 'bahan_id', 'qty_bahan', 'satuan'];
}
