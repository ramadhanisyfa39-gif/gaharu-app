<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penggajian extends Model
{
    protected $table = 'penggajian';
    public $timestamps = false;
    protected $fillable = ['karyawan_id', 'periode_bulan_tahun', 'gaji_pokok', 'lembur', 'potongan', 'total_gaji_bersih', 'tanggal_transfer'];

    public function karyawan(): BelongsTo
    {
        // Pastikan model Karyawan sudah di-import di atas atau tulis lengkap path-nya
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'id');
    }
}
