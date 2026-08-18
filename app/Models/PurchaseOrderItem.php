<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item pembelian — harga beli & qty dikunci saat PO dibuat (snapshot harga,
 * schema-database.md §6.2). Tanpa activity log: baris anak mengikuti PO.
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property string $qty
 * @property string $unit_cost harga beli per unit saat PO dibuat (snapshot)
 * @property string $subtotal = qty × unit_cost
 */
#[Fillable(['purchase_order_id', 'product_id', 'qty', 'unit_cost', 'subtotal'])]
class PurchaseOrderItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
