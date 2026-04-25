<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $table = 'journals';
    protected $fillable = ['tanggal', 'deskripsi', 'no_ref', 'source_type', 'source_id', 'created_by'];

    public function items()
    {
        return $this->hasMany(JournalItem::class, 'journal_id');
    }
}
