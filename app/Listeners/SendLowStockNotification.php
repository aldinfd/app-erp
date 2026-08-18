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

        $body = sprintf(
            'Stok %s (%s) tersisa %s, sudah menyentuh reorder point %s. Segera lakukan pembelian ke vendor.',
            $event->product->name,
            $event->product->sku,
            number_format($event->stockQty, 2, ',', '.'),
            number_format((float) $event->product->reorder_point, 2, ',', '.'),
        );

        User::role(['admin', 'staff_gudang'])->get()->each(
            fn (User $user) => $user->notify(new SystemNotification($title, $body)),
        );
    }
}
