<?php

namespace App\Services;

use App\Models\SalesOrder;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * Integrasi Midtrans Snap — diisolasi di service agar mudah di-swap/mock
 * saat test (plan §0: integrasi pihak ketiga di service layer).
 */
class MidtransService
{
    /**
     * Buat transaksi Snap dan kembalikan redirect_url pembayaran.
     * Config statis di-set dari config/services.php pada setiap panggilan
     * agar tidak memakai state basi antar request/test.
     *
     * @throws \Exception dibalut Midtrans (server key kosong, jaringan, API error)
     */
    public function createSnapTransaction(SalesOrder $order): string
    {
        $this->configure();

        $order->loadMissing('customer', 'items.product.unit');

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) round((float) $order->grand_total),
            ],
            'item_details' => $this->itemDetails($order),
            'customer_details' => [
                'first_name' => $order->customer->name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ],
            'callbacks' => [
                'finish' => route('payment.finish'),
            ],
        ];

        return Snap::createTransaction($params)->redirect_url;
    }

    /**
     * Verifikasi signature HTTP notification Midtrans:
     * sha512(order_id + status_code + gross_amount + server_key).
     */
    public function verifySignature(array $payload): bool
    {
        $expected = hash('sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            config('services.midtrans.server_key')
        );

        return is_string($payload['signature_key'] ?? null)
            && hash_equals($expected, (string) $payload['signature_key']);
    }

    private function configure(): void
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = (bool) config('services.midtrans.is_sanitized');
        Config::$is3ds = (bool) config('services.midtrans.is_3ds');
    }

    /**
     * Midtrans mensyaratkan quantity integer, sementara qty lokal bisa pecahan
     * (kg) — jadi tiap baris dikirim quantity = 1 dengan price = subtotal baris;
     * qty asli ditempel di nama item. Snap menghitung ulang gross_amount dari
     * jumlah baris ini, jadi total tetap = grand_total.
     *
     * @return array<int, array{id: string, price: int, quantity: int, name: string}>
     */
    private function itemDetails(SalesOrder $order): array
    {
        return $order->items->map(function ($item) {
            $qty = rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.');

            return [
                'id' => $item->product->sku,
                'price' => (int) round((float) $item->subtotal),
                'quantity' => 1,
                'name' => sprintf('%s (%s %s)', $item->product->name, $qty, $item->product->unit?->abbreviation ?? ''),
            ];
        })->all();
    }
}
