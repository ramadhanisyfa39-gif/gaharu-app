<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawan';
    protected $fillable = ['nama_karyawan', 'jabatan', 'jenis_tenaga_kerja', 'departemen'];
    public $timestamps = false;
}
