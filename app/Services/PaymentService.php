<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Proses HTTP notification (webhook) Midtrans.
 *
 * Status sukses (settlement / capture-accept) memicu cascade dalam SATU DB
 * transaction: payment → paid, invoice → paid, SO → paid, kurangi stok via
 * StockService, auto-jurnal via JournalService (schema-database.md §10).
 *
 * Idempoten untuk notifikasi duplikat/konkuren: baris payment dikunci
 * (lockForUpdate) lalu dicek ulang di dalam transaction.
 *
 * Kegagalan cascade (mis. stok kurang, mapping jurnal hilang) TIDAK
 * mengembalikan error ke Midtrans: response tetap 200 — retry Midtrans
 * tidak akan memperbaiki masalahnya, malah spam. Payment dibiarkan pending
 * sehingga notifikasi berikutnya (setelah masalah ditangani) mencoba lagi.
 * Admin/staff_finance tetap diberi tahu lewat notifikasi.
 */
class PaymentService
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly MidtransService $midtrans,
        private readonly StockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  body JSON notification Midtrans
     */
    public function handleMidtransNotification(array $payload): void
    {
        if (! $this->midtrans->verifySignature($payload)) {
            abort(401, 'Signature Midtrans tidak valid.');
        }

        $this->applyMidtransPayload($payload);
    }

    /**
     * Tarik status terbaru langsung dari Midtrans Status API dan terapkan ke
     * payment lokal. Dipakai saat customer kembali dari Midtrans (halaman
     * struk) sebelum webhook sampai — mis. dev lokal tanpa URL publik.
     *
     * Payload berasal dari API yang kita minta sendiri dengan server key,
     * jadi tidak perlu verifikasi signature (itu hanya untuk push
     * notification). Aman dipanggil berkali-kali: markPaid idempoten.
     */
    public function syncGatewayStatus(Payment $payment): void
    {
        if ($payment->gateway_ref === null) {
            return;
        }

        $payload = $this->midtrans->getTransactionStatus($payment->gateway_ref);

        if ($payload === null) {
            return; // transaksi tidak ditemukan / gangguan jaringan — status lokal dibiarkan
        }

        try {
            $this->applyMidtransPayload($payload);
        } catch (Throwable) {
            // Halaman struk tidak boleh 500 hanya karena sync gagal —
            // status lokal tetap pending, webhook tetap jalan normal.
            Log::warning("Gagal sync status Midtrans untuk order {$payment->gateway_ref}.");
        }
    }

    /**
     * State machine status Midtrans — dipakai webhook (setelah verifikasi
     * signature) maupun sync dari Status API.
     *
     * @param  array<string, mixed>  $payload
     */
    private function applyMidtransPayload(array $payload): void
    {
        $payment = Payment::query()->where('gateway_ref', $payload['order_id'] ?? null)->first();

        if ($payment === null) {
            // Order tak dikenal: log saja, tetap 200 agar Midtrans tidak retry storm.
            Log::warning('Payload Midtrans untuk order tak dikenal.', ['order_id' => $payload['order_id'] ?? null]);

            return;
        }

        $status = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        if ($status === Payment::STATUS_SETTLEMENT) {
            $this->markPaid($payment, Payment::STATUS_SETTLEMENT);

            return;
        }

        if ($status === Payment::STATUS_CAPTURE) {
            if ($fraudStatus === 'challenge') {
                return; // menunggu keputusan manual di dashboard Midtrans (MAP)
            }

            $this->markPaid($payment, Payment::STATUS_CAPTURE);

            return;
        }

        if (in_array($status, [Payment::STATUS_DENY, Payment::STATUS_EXPIRE, Payment::STATUS_CANCEL, Payment::STATUS_REFUND], true)) {
            $payment->update(['status' => $status]);

            return;
        }

        // pending dan status tak dikenal: tidak ada perubahan state.
    }

    /**
     * Cascade transisi paid — dipanggil untuk settlement/capture-accept.
     */
    private function markPaid(Payment $payment, string $status): void
    {
        try {
            DB::transaction(function () use ($payment, $status): void {
                $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

                if ($locked->paid_at !== null || in_array($locked->status, Payment::PAID_STATUSES, true)) {
                    return; // sudah diproses — notifikasi duplikat/konkuren diabaikan
                }

                $locked->update(['status' => $status, 'paid_at' => now()]);

                $invoice = Invoice::query()->whereKey($locked->invoice_id)->lockForUpdate()->firstOrFail();
                $invoice->update(['amount_paid' => $invoice->amount, 'status' => Invoice::STATUS_PAID]);

                $order = SalesOrder::query()->whereKey($invoice->sales_order_id)->firstOrFail();
                $order->update(['status' => SalesOrder::STATUS_PAID]);

                foreach ($order->items as $item) {
                    $this->stockService->deduct(
                        $item->product,
                        (float) $item->qty,
                        'sales_order',
                        $order->id,
                        "Penjualan {$order->order_number}",
                    );
                }

                $this->journalService->postSalesPayment($locked);
            });
        } catch (InsufficientStockException $e) {
            report($e);

            $this->notifyOps(
                'Stok tidak cukup untuk order '.$payment->gateway_ref,
                'Pembayaran diterima Midtrans namun stok kurang. Restock produk lalu tunggu notifikasi ulang, atau refund manual dari dashboard Midtrans.',
            );
        } catch (Throwable $e) {
            report($e);

            $this->notifyOps(
                'Gagal memproses pembayaran order '.$payment->gateway_ref,
                'Pembayaran diterima Midtrans namun terjadi error saat memproses order (cek log). Payment dibiarkan pending agar bisa dicoba ulang.',
            );
        }
    }

    private function notifyOps(string $title, string $body): void
    {
        User::role(['admin', 'staff_finance'])->get()->each(
            fn (User $user) => $user->notify(new SystemNotification($title, $body)),
        );
    }
}
