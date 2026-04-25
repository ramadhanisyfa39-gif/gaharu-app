<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepBtklBop extends Model
{
    protected $table = 'resep_btkl_bop';
    protected $fillable = ['produk_id', 'output_qty', 'satuan_output', 'btkl_per_batch', 'bop_per_batch'];

    public function bahanBaku()
    {
        return $this->hasMany(ResepBahanBaku::class, 'resep_id');
    }
}
