<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiayaBulanan extends Model
{
    protected $table = 'biaya_bulanan';
    protected $fillable = ['periode_bulan_tahun', 'jenis_biaya', 'total_biaya', 'persen_hpp', 'persen_beban', 'nilai_hpp', 'nilai_beban', 'tanggal_bayar', 'keterangan', 'created_by'];
}
