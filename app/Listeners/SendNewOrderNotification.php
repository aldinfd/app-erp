<?php

namespace App\Listeners;

use App\Events\SalesOrderCreated;
use App\Models\User;
use App\Notifications\SystemNotification;

/**
 * Kirim notifikasi "order baru" (in-app + email) ke admin & staff finance
 * (pengelola penjualan sesuai RBAC Phase 4). Listener ditemukan otomatis
 * oleh Laravel event discovery.
 */
class SendNewOrderNotification
{
    public function handle(SalesOrderCreated $event): void
    {
        $order = $event->order->loadMissing('customer');

        User::role(['admin', 'staff_finance'])->get()->each(
            fn (User $user) => $user->notify(new SystemNotification(
                'Order baru '.$order->order_number,
                sprintf(
                    'Order dari %s sebesar Rp %s menunggu pembayaran.',
                    $order->customer->name,
                    number_format((float) $order->grand_total, 0, ',', '.'),
                ),
            )),
        );
    }
}
