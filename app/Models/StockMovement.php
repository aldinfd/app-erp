<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Riwayat perubahan stok — append-only: baris tidak pernah diubah/dihapus
 * (schema-database.md §7.1). Tidak memakai trait activity log karena tabel
 * ini sendiri sudah merupakan audit trail stok.
 *
 * @property int $id
 * @property int $product_id
 * @property string $type nilai sah: in, out, adjust
 * @property string $qty signed delta: in = positif, out = negatif, adjust = ±
 * @property string $before_qty stok sebelum perubahan
 * @property string $after_qty invariant: after_qty = before_qty + qty
 * @property string|null $reference_type nilai sah: sales_order, purchase_order, stock_opname
 * @property int|null $reference_id
 * @property int|null $user_id siapa yang melakukan perubahan
 * @property string|null $note wajib untuk type adjust (alasan opname)
 * @property Carbon|null $created_at
 */
#[Fillable(['product_id', 'type', 'qty', 'before_qty', 'after_qty', 'reference_type', 'reference_id', 'user_id', 'note'])]
class StockMovement extends Model
{
    /**
     * Tanpa updated_at — baris tidak pernah diubah setelah dibuat.
     */
    public const UPDATED_AT = null;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_ADJUST = 'adjust';

    /** @var list<string> */
    public const TYPES = [self::TYPE_IN, self::TYPE_OUT, self::TYPE_ADJUST];

    /** Label tipe untuk PDF/Excel kartu stok (plan Phase 8). */
    public const TYPE_LABELS = [
        self::TYPE_IN => 'Masuk',
        self::TYPE_OUT => 'Keluar',
        self::TYPE_ADJUST => 'Penyesuaian',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'before_qty' => 'decimal:2',
            'after_qty' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
