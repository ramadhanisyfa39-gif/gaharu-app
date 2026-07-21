<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $table = 'journals';
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
    public function details(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'journal_id')
            ->whereIn('journal_type', ['jurnal_umum', 'jurnal', 'closing', 'opening']);
    }

    public static function isPeriodClosed($date): bool
    {
        if (!$date) return false;

        $latestClosing = self::where('source_type', 'closing')
            ->orderBy('tanggal', 'desc')
            ->first();

        if (!$latestClosing) {
            return false;
        }

        $closingDate = \Carbon\Carbon::parse($latestClosing->tanggal)->endOfMonth();
        $targetDate  = \Carbon\Carbon::parse($date);

        return $targetDate->lte($closingDate);
    }
}
