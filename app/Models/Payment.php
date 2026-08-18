<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Pembayaran customer via Midtrans (schema-database.md §5.4).
 * `gateway_ref` (order_number SO) UNIQUE = kunci idempotensi webhook.
 *
 * Kolom `method` yang sah: midtrans, bank_transfer, cash.
 * Kolom `status` yang sah: pending, settlement, capture, deny, expire, cancel, refund.
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $amount
 * @property string $method
 * @property string|null $gateway
 * @property string|null $gateway_ref order_id Midtrans (= order_number SO)
 * @property string $status
 * @property Carbon|null $paid_at
 */
#[Fillable(['invoice_id', 'amount', 'method', 'gateway', 'gateway_ref', 'status', 'paid_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasActivity, HasFactory;

    public const METHOD_MIDTRANS = 'midtrans';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CASH = 'cash';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLEMENT = 'settlement';

    public const STATUS_CAPTURE = 'capture';

    public const STATUS_DENY = 'deny';

    public const STATUS_EXPIRE = 'expire';

    public const STATUS_CANCEL = 'cancel';

    public const STATUS_REFUND = 'refund';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_SETTLEMENT, self::STATUS_CAPTURE,
        self::STATUS_DENY, self::STATUS_EXPIRE, self::STATUS_CANCEL, self::STATUS_REFUND,
    ];

    /** Status Midtrans yang berarti uang benar-benar masuk. */
    public const PAID_STATUSES = [self::STATUS_SETTLEMENT, self::STATUS_CAPTURE];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
