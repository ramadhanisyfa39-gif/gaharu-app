<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penggajian extends Model
{
    protected $table = 'penggajian';

    public $timestamps = false;

    protected $fillable = ['karyawan_id', 'periode_bulan_tahun', 'gaji_pokok', 'tunjangan_transport', 'tunjangan_makan', 'lembur', 'bonus_target', 'bonus_tanggal_merah', 'bonus_birthday', 'bonus_dll', 'potongan_inventaris', 'potongan_terlambat', 'total_gaji_bersih', 'status'];

    public function karyawan(): BelongsTo
    {
        // Pastikan model Karyawan sudah di-import di atas atau tulis lengkap path-nya
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'id');
    }
}
