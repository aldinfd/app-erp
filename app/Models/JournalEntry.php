<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Jurnal keuangan — immutable: koreksi lewat jurnal reversal, bukan edit/delete
 * (schema-database.md §8.1). Tanpa activity log: baris ini sendiri merupakan
 * catatan keuangan final, dibuat hanya oleh JournalService.
 *
 * Kolom `source` yang sah: sales_payment, purchase_received, purchase_payment, manual.
 *
 * @property int $id
 * @property string $entry_number format JE-YYYYMM-####
 * @property Carbon $entry_date
 * @property string $description
 * @property string $source
 * @property string|null $reference_type polymorphic: payment, purchase_order, vendor_payment, dll
 * @property int|null $reference_id
 * @property int|null $posted_by
 */
#[Fillable(['entry_number', 'entry_date', 'description', 'source', 'reference_type', 'reference_id', 'posted_by'])]
class JournalEntry extends Model
{
    public const SOURCE_SALES_PAYMENT = 'sales_payment';

    public const SOURCE_PURCHASE_RECEIVED = 'purchase_received';

    public const SOURCE_PURCHASE_PAYMENT = 'purchase_payment';

    public const SOURCE_MANUAL = 'manual';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_SALES_PAYMENT, self::SOURCE_PURCHASE_RECEIVED,
        self::SOURCE_PURCHASE_PAYMENT, self::SOURCE_MANUAL,
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
