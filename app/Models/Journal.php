<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $table = 'journals';
    public $timestamps = false;
    protected $fillable = ['tanggal', 'deskripsi', 'no_ref', 'source_type', 'source_id', 'created_by'];

    public function details(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'journal_id');
    }
}
