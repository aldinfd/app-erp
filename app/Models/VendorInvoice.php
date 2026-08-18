<?php

namespace App\Models;

use Database\Factories\VendorInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Kolom `status` yang sah: unpaid, partial, paid, void (schema-database.md
 * §6.3). Satu PO maksimal satu invoice vendor (purchase_order_id UNIQUE);
 * nomor invoice milik vendor (bukan digenerate sistem).
 *
 * @property int $id
 * @property string $vendor_invoice_number
 * @property int $purchase_order_id
 * @property Carbon $invoice_date
 * @property Carbon|null $due_date
 * @property string $amount
 * @property string $amount_paid diupdate saat vendor payment masuk
 * @property string $status
 */
#[Fillable(['vendor_invoice_number', 'purchase_order_id', 'invoice_date', 'due_date', 'amount', 'amount_paid', 'status'])]
class VendorInvoice extends Model
{
    /** @use HasFactory<VendorInvoiceFactory> */
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
            'invoice_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
