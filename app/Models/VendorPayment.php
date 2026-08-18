<?php

namespace App\Models;

use Database\Factories\VendorPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Pembayaran ke vendor — transfer/cash manual, bukan payment gateway
 * (schema-database.md §6.4, keputusan #4).
 *
 * Kolom `method` yang sah: bank_transfer, cash.
 *
 * @property int $id
 * @property int $vendor_invoice_id
 * @property string $amount
 * @property string $method
 * @property string|null $reference_no no. bukti transfer
 * @property Carbon $paid_at
 * @property string|null $notes
 */
#[Fillable(['vendor_invoice_id', 'amount', 'method', 'reference_no', 'paid_at', 'notes'])]
class VendorPayment extends Model
{
    /** @use HasFactory<VendorPaymentFactory> */
    use HasActivity, HasFactory;

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CASH = 'cash';

    /** @var list<string> */
    public const METHODS = [self::METHOD_BANK_TRANSFER, self::METHOD_CASH];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function vendorInvoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
