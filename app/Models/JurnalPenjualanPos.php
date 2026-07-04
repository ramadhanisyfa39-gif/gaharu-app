<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalPenjualanPos extends Model
{
    use HasFactory;

    // 1. Definisikan nama tabel di database kamu
    protected $table = 'jurnal_penjualan_pos';

    // 2. Daftarkan kolom yang boleh diisi
    protected $fillable = [
        'tanggal',
        'no_ref',
        'deskripsi',
        'source_type',
        'source_id',
        'created_by',
    ];

    /**
     * Relasi ke tabel journal_items.
     * Karena journal_items tidak memiliki model (masih tabel biasa), 
     * relasi ini tetap aman digunakan dengan query builder bawaan Eloquent.
     */
    public function items()
    {
        // Menghubungkan id jurnal ini ke journal_id di tabel journal_items
        return $this->hasMany(\App\Models\JournalItem::class, 'journal_id', 'id')
            ->where('journal_type', 'jurnal_penjualan_pos');
        // Filter Opsi A yang kamu pakai di controller
    }
}
