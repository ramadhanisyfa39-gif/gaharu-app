<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    protected $table = 'journal_items';
    protected $fillable = ['journal_id', 'account_id', 'debit', 'kredit'];
    public $timestamps = false;
}
