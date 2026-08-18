<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dipicu StockService saat stok produk turun menyentuh/menembus reorder point
 * (sebelumnya DI ATAS reorder point, sekarang <= reorder point).
 * Hanya momen "penurunan pertama" yang dipicu agar tidak spam notifikasi
 * pada setiap movement selama stok masih di bawah ambang.
 */
class LowStockDetected
{
    use Dispatchable;

    public function __construct(
        public readonly Product $product,
        public readonly float $stockQty,
    ) {}
}
