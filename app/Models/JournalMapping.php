<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Mapping akun untuk auto-jurnal — UNIQUE(transaction_type, account_key);
 * id akun tidak pernah di-hard-code di service (schema-database.md §8.3).
 *
 * Kolom `transaction_type` yang sah: sales_payment, purchase_received, purchase_payment.
 * Kolom `account_key` yang sah: kas_bank, pendapatan_penjualan, utang_ppn,
 * persediaan, hpp, hutang_vendor.
 *
 * @property int $id
 * @property string $transaction_type
 * @property string $account_key
 * @property int $account_id
 */
#[Fillable(['transaction_type', 'account_key', 'account_id'])]
class JournalMapping extends Model
{
    use HasActivity;

    public const TRANSACTION_TYPE_SALES_PAYMENT = 'sales_payment';

    public const TRANSACTION_TYPE_PURCHASE_RECEIVED = 'purchase_received';

    public const TRANSACTION_TYPE_PURCHASE_PAYMENT = 'purchase_payment';

    public const KEY_KAS_BANK = 'kas_bank';

    public const KEY_PENDAPATAN_PENJUALAN = 'pendapatan_penjualan';

    public const KEY_UTANG_PPN = 'utang_ppn';

    public const KEY_PERSEDIAAN = 'persediaan';

    public const KEY_HPP = 'hpp';

    public const KEY_HUTANG_VENDOR = 'hutang_vendor';

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
