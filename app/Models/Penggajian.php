<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    protected $table = 'penggajian';
    protected $fillable = ['karyawan_id', 'periode_bulan_tahun', 'gaji_pokok', 'lembur', 'potongan', 'total_gaji_bersih', 'tanggal_transfer'];
}
