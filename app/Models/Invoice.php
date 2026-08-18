<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Kolom `status` yang sah: unpaid, partial, paid, void (schema-database.md §5.3).
 * Satu sales order maksimal satu invoice (sales_order_id UNIQUE).
 *
 * @property int $id
 * @property string $invoice_number format INV-YYYYMM-####
 * @property int $sales_order_id
 * @property Carbon $issued_date
 * @property Carbon|null $due_date
 * @property string $amount = grand_total SO saat terbit
 * @property string $amount_paid diupdate saat payment masuk
 * @property string $status
 */
#[Fillable(['invoice_number', 'sales_order_id', 'issued_date', 'due_date', 'amount', 'amount_paid', 'status'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasActivity, HasFactory;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_UNPAID, self::STATUS_PARTIAL, self::STATUS_PAID, self::STATUS_VOID];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
