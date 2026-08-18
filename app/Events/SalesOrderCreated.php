<?php

namespace App\Events;

use App\Models\SalesOrder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dipicu CheckoutService setelah sales order guest berhasil dibuat & commit.
 * Tidak dipicu untuk order yang rollback (pola yang sama dengan LowStockDetected).
 */
class SalesOrderCreated
{
    use Dispatchable;

    public function __construct(
        public readonly SalesOrder $order,
    ) {}
}
