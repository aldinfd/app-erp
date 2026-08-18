<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris jurnal — aturan: tepat satu sisi debit/credit > 0, Σ debit = Σ credit
 * per entry (divalidasi JournalService, schema-database.md §8.2).
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id harus akun is_postable = 1
 * @property string $debit
 * @property string $credit
 */
#[Fillable(['journal_entry_id', 'account_id', 'debit', 'credit'])]
class JournalLine extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
