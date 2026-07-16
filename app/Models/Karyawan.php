<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    protected $table = 'karyawan';
    public $timestamps = false;
    protected $fillable = [
        'nama_karyawan',
        'jabatan',
        'jenis_tenaga_kerja',
        'departemen',
        'gaji_pokok',
    ];

    protected $casts = [
        'gaji_pokok' => 'float',
    ];


    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'karyawan_id', 'id');
    }
}
