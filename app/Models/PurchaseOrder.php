<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Kolom `status` yang sah: draft, ordered, received, paid, cancelled
 * (schema-database.md §6.1, keputusan #5). Alur: draft → ordered →
 * received → paid.
 *
 * @property int $id
 * @property string $po_number format PO-YYYYMM-####
 * @property int $vendor_id
 * @property Carbon $order_date
 * @property Carbon|null $expected_date estimasi barang datang
 * @property string $status
 * @property string $subtotal
 * @property string $tax
 * @property string $grand_total = subtotal + tax
 * @property string|null $notes
 */
#[Fillable(['po_number', 'vendor_id', 'order_date', 'expected_date', 'status', 'subtotal', 'tax', 'grand_total', 'notes'])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasActivity, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_ORDERED, self::STATUS_RECEIVED,
        self::STATUS_PAID, self::STATUS_CANCELLED,
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function vendorInvoice(): HasOne
    {
        return $this->hasOne(VendorInvoice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
