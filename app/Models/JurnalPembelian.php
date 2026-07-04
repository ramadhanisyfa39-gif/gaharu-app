<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalPembelian extends Model
{
    use HasFactory;

    // Menentukan nama tabel karena tidak menggunakan penamaan jamak standar (plural)
    protected $table = 'jurnal_pembelian';

    // Kolom yang diizinkan untuk pengisian massal (mass assignment)
    protected $fillable = [
        'tanggal',
        'deskripsi',
        'no_ref',
        'source_type',
        'source_id',
        'tahap',
        'created_by',
    ];

    // Mengonversi kolom tanggal menjadi objek Carbon secara otomatis
    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relasi ke data Pembelian (Source)
     * Menghubungkan jurnal dengan invoice pembelian asal.
     */
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'source_id');
    }

    /**
     * Relasi ke item jurnal (Journal Items)
     * Mengambil semua baris debit/kredit yang dimiliki oleh jurnal ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'journal_id')
            ->where('journal_type', 'jurnal_pembelian');
    }

    /**
     * Relasi ke model User atau Pengguna
     * Mencatat siapa yang membuat/menyimpan jurnal ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
