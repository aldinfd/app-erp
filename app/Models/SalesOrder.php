<?php

namespace App\Models;

use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Kolom `status` yang sah: draft, confirmed, paid, cancelled (schema-database.md §5.1).
 *
 * @property int $id
 * @property string $order_number format SO-YYYYMM-####
 * @property int $customer_id
 * @property Carbon $order_date
 * @property string $status
 * @property string $subtotal
 * @property string $tax
 * @property string $shipping
 * @property string $grand_total = subtotal + tax + shipping
 * @property string|null $notes
 */
#[Fillable(['order_number', 'customer_id', 'order_date', 'status', 'subtotal', 'tax', 'shipping', 'grand_total', 'notes'])]
class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasActivity, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_CONFIRMED, self::STATUS_PAID, self::STATUS_CANCELLED];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
