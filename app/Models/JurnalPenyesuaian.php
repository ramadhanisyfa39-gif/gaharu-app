<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalPenyesuaian extends Model
{
    protected $table = 'jurnal_penyesuaian';
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'deskripsi',
        'no_ref',
        'source_type',
        'source_id',
        'created_by',
        'status'
    ];

    // Relasi ke detail journal_items
    public function details()
    {
        // Parameter: NamaModelDetail, foreign_key_di_detail, local_key_di_header
        return $this->hasMany(JournalItem::class, 'journal_id', 'id')
            ->whereIn('journal_type', [self::class, 'jurnal_penyesuaian']);
    }
}
