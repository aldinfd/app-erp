<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

/**
 * Dilempar StockService::deduct() bila stok tidak cukup.
 * DB transaction di-rollback — stok tidak berubah, tidak ada movement tersimpan.
 */
class InsufficientStockException extends RuntimeException
{
    public static function forProduct(Product $product, float $needed, float $available): self
    {
        return new self(sprintf(
            'Stok %s (%s) tidak cukup: dibutuhkan %.2f, tersedia %.2f.',
            $product->name,
            $product->sku,
            $needed,
            $available,
        ));
    }
}
