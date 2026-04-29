<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalItem extends Model
{
    protected $table = 'journal_items';
    protected $fillable = ['journal_id', 'account_id', 'debit', 'kredit'];
    public $timestamps = false;

    public function coa(): BelongsTo
    {
        // Parameter: (ModelTujuan, foreign_key_di_sini, primary_key_di_tujuan)
        return $this->belongsTo(ChartOfAccount::class, 'account_id', 'id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id', 'id');
    }
}
