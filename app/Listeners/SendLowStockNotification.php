<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Models\User;
use App\Notifications\SystemNotification;

/**
 * Kirim notifikasi "stok menipis" (in-app + email) ke admin & staff gudang.
 * Listener ditemukan otomatis oleh Laravel event discovery.
 */
class SendLowStockNotification
{
    public function handle(LowStockDetected $event): void
    {
        $title = 'Stok menipis: '.$event->product->name;

        // Angka bulat untuk satuan non-pecahan (pcs), 2 desimal untuk satuan seperti kg.
        $decimals = $event->product->unit?->allows_fraction ? 2 : 0;

        $body = sprintf(
            'Stok %s (%s) tersisa %s, sudah menyentuh reorder point %s. Segera lakukan pembelian ke vendor.',
            $event->product->name,
            $event->product->sku,
            number_format($event->stockQty, $decimals, ',', '.'),
            number_format((float) $event->product->reorder_point, $decimals, ',', '.'),
        );

        User::role(['admin', 'staff_gudang'])->get()->each(
            fn (User $user) => $user->notify(new SystemNotification($title, $body)),
        );
    }
}
