<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item penjualan — harga & qty dikunci saat order dibuat (snapshot harga,
 * schema-database.md §5.2). Tanpa activity log: baris anak mengikuti order.
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int $product_id
 * @property string $qty
 * @property string $unit_price harga jual saat transaksi (snapshot)
 * @property string $subtotal = qty × unit_price
 */
#[Fillable(['sales_order_id', 'product_id', 'qty', 'unit_price', 'subtotal'])]
class SalesOrderItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
