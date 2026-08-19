<?php

namespace Tests\Feature\Sales;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\JournalMapping;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\CheckoutService;
use App\Services\MidtransService;
use App\Services\PaymentService;
use App\Services\StockService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\JournalMappingSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-TEST-KEY';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        RateLimiter::clear('webhooks');

        // Order dibuat lewat CheckoutService (jalur asli) — event/listener
        // butuh role & user; jurnal butuh mapping akun.
        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            ChartOfAccountSeeder::class,
            JournalMappingSeeder::class,
        ]);
    }

    /**
     * Helper: order valid via jalur checkout asli.
     *
     * @return array{0: SalesOrder, 1: Product}
     */
    private function makeOrder(float $qty = 2): array
    {
        $product = Product::factory()->create(['stock_qty' => 100, 'selling_price' => 25000, 'cost_price' => 10000]);

        $order = app(CheckoutService::class)->createOrder(
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '08123456789', 'address' => 'Jl. Mawar No. 1'],
            [['product_id' => $product->id, 'qty' => $qty]],
        );

        return [$order, $product];
    }

    /**
     * Helper: payload notification dengan signature valid.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function signedPayload(string $orderId, string $transactionStatus, string $grossAmount, array $extra = []): array
    {
        $payload = array_merge([
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => $transactionStatus,
            'payment_type' => 'qris',
            'transaction_id' => 'txn-'.uniqid(),
        ], $extra);

        $payload['signature_key'] = hash('sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].self::SERVER_KEY,
        );

        return $payload;
    }

    public function test_sync_gateway_status_settles_payment_and_is_idempotent(): void
    {
        [$order, $product] = $this->makeOrder(qty: 2);

        // Payload dari Status API — tidak punya signature (tidak diperlukan
        // untuk pull, itu hanya untuk push notification).
        $this->mock(MidtransService::class)
            ->shouldReceive('getTransactionStatus')
            ->andReturn([
                'order_id' => $order->order_number,
                'transaction_status' => Payment::STATUS_SETTLEMENT,
                'gross_amount' => '50000.00',
            ]);

        app(PaymentService::class)->syncGatewayStatus(Payment::query()->sole());

        $this->assertSame(Payment::STATUS_SETTLEMENT, Payment::query()->sole()->status);
        $this->assertSame(SalesOrder::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(98.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(1, JournalEntry::count());

        // Customer refresh struk → sync ulang tidak boleh dobel cascade.
        app(PaymentService::class)->syncGatewayStatus(Payment::query()->sole());

        $this->assertSame(1, JournalEntry::count());
        $this->assertSame(1, StockMovement::where('type', StockMovement::TYPE_OUT)->count());
        $this->assertSame(98.0, (float) $product->fresh()->stock_qty);
    }

    public function test_sync_gateway_status_ignores_unreachable_gateway(): void
    {
        [$order] = $this->makeOrder(qty: 1);

        $this->mock(MidtransService::class)
            ->shouldReceive('getTransactionStatus')
            ->andReturn(null);

        app(PaymentService::class)->syncGatewayStatus(Payment::query()->sole());

        $this->assertSame(Payment::STATUS_PENDING, Payment::query()->sole()->status);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_settlement_notification_marks_everything_paid(): void
    {
        [$order, $product] = $this->makeOrder(qty: 2);

        $response = $this->postJson('/webhooks/midtrans', $this->signedPayload(
            $order->order_number, Payment::STATUS_SETTLEMENT, '50000.00',
        ));

        $response->assertStatus(204);

        $payment = Payment::query()->sole();
        $this->assertSame(Payment::STATUS_SETTLEMENT, $payment->status);
        $this->assertNotNull($payment->paid_at);

        $order = $order->fresh();
        $this->assertSame(SalesOrder::STATUS_PAID, $order->status);
        $this->assertSame('paid', $order->invoice->status);
        $this->assertSame(50000.0, (float) $order->invoice->amount_paid);

        // Stok berkurang + movement out negatif dengan reference sales_order.
        $this->assertSame(98.0, (float) $product->fresh()->stock_qty);
        $movement = StockMovement::query()->where('type', StockMovement::TYPE_OUT)->sole();
        $this->assertSame(-2.0, (float) $movement->qty);
        $this->assertSame('sales_order', $movement->reference_type);
        $this->assertSame($order->id, $movement->reference_id);
        $this->assertStringContainsString($order->order_number, (string) $movement->note);

        // Auto-jurnal balance: D kas = grand_total, C pendapatan = subtotal,
        // plus COGS — D HPP / C persediaan = Σ qty × cost_price (2 × 10000).
        $entry = JournalEntry::query()->sole();
        $this->assertSame(JournalEntry::SOURCE_SALES_PAYMENT, $entry->source);
        $this->assertSame('payment', $entry->reference_type);
        $this->assertSame($payment->id, $entry->reference_id);

        $accountId = fn (string $code): int => ChartOfAccount::where('code', $code)->value('id');
        $lines = JournalLine::query()->where('journal_entry_id', $entry->id)->get();
        $this->assertSame(4, $lines->count());
        $this->assertSame(50000.0, (float) $lines->firstWhere('account_id', $accountId('1-1000'))->debit);
        $this->assertSame(50000.0, (float) $lines->firstWhere('account_id', $accountId('4-1000'))->credit);
        $this->assertSame(20000.0, (float) $lines->firstWhere('account_id', $accountId('5-1000'))->debit);
        $this->assertSame(20000.0, (float) $lines->firstWhere('account_id', $accountId('1-3000'))->credit);
        $this->assertSame(70000.0, round((float) $lines->sum('debit'), 2));
        $this->assertSame(70000.0, round((float) $lines->sum('credit'), 2));
    }

    public function test_webhook_is_idempotent_for_duplicate_notifications(): void
    {
        [$order, $product] = $this->makeOrder(qty: 2);
        $payload = $this->signedPayload($order->order_number, Payment::STATUS_SETTLEMENT, '50000.00');

        $this->postJson('/webhooks/midtrans', $payload)->assertStatus(204);
        $this->postJson('/webhooks/midtrans', $payload)->assertStatus(204);

        $this->assertSame(1, StockMovement::where('type', StockMovement::TYPE_OUT)->count());
        $this->assertSame(1, JournalEntry::count());
        $this->assertSame(98.0, (float) $product->fresh()->stock_qty, 'Stok tidak boleh terdeduct dua kali.');
    }

    public function test_capture_with_fraud_accept_cascades_like_settlement(): void
    {
        [$order, $product] = $this->makeOrder(qty: 1);

        $this->postJson('/webhooks/midtrans', $this->signedPayload(
            $order->order_number, Payment::STATUS_CAPTURE, '25000.00', ['fraud_status' => 'accept'],
        ))->assertStatus(204);

        $this->assertSame(Payment::STATUS_CAPTURE, Payment::query()->sole()->status);
        $this->assertSame(SalesOrder::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(99.0, (float) $product->fresh()->stock_qty);
    }

    public function test_capture_with_challenge_leaves_payment_pending(): void
    {
        [$order] = $this->makeOrder(qty: 1);

        $this->postJson('/webhooks/midtrans', $this->signedPayload(
            $order->order_number, Payment::STATUS_CAPTURE, '25000.00', ['fraud_status' => 'challenge'],
        ))->assertStatus(204);

        $this->assertSame(Payment::STATUS_PENDING, Payment::query()->sole()->status);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_invalid_signature_is_rejected_with_401(): void
    {
        [$order] = $this->makeOrder(qty: 1);

        $payload = $this->signedPayload($order->order_number, Payment::STATUS_SETTLEMENT, '25000.00');
        $payload['signature_key'] = str_repeat('0', 128);

        $this->postJson('/webhooks/midtrans', $payload)->assertStatus(401);
        $this->assertSame(Payment::STATUS_PENDING, Payment::query()->sole()->status);
    }

    public function test_unknown_order_id_returns_204_without_side_effects(): void
    {
        $this->postJson('/webhooks/midtrans', $this->signedPayload(
            'SO-202608-9999', Payment::STATUS_SETTLEMENT, '10000.00',
        ))->assertStatus(204);

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_deny_and_expire_update_payment_status_only(): void
    {
        foreach ([Payment::STATUS_DENY, Payment::STATUS_EXPIRE] as $status) {
            $fresh = $this->makeOrder(qty: 1);

            $this->postJson('/webhooks/midtrans', $this->signedPayload(
                $fresh[0]->order_number, $status, '25000.00',
            ))->assertStatus(204);

            $payment = Payment::query()->where('gateway_ref', $fresh[0]->order_number)->sole();
            $this->assertSame($status, $payment->status);
            $this->assertNull($payment->paid_at);
            $this->assertSame(SalesOrder::STATUS_CONFIRMED, $fresh[0]->fresh()->status);
            $this->assertSame(0, JournalEntry::count());
        }
    }

    public function test_insufficient_stock_at_settlement_notifies_admin_and_keeps_state(): void
    {
        [$order, $product] = $this->makeOrder(qty: 2);

        // Stok dikeringkan setelah checkout — webhook harus gagal atomik.
        app(StockService::class)->adjust($product->fresh(), 0, 'Uji stok habis');

        Notification::fake();

        $this->postJson('/webhooks/midtrans', $this->signedPayload(
            $order->order_number, Payment::STATUS_SETTLEMENT, '50000.00',
        ))->assertStatus(204);

        $payment = Payment::query()->sole();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
        $this->assertSame(0, StockMovement::where('type', StockMovement::TYPE_OUT)->count(), 'Tidak boleh ada movement out saat cascade rollback.');
        $this->assertSame(0, JournalEntry::count());

        $admin = User::where('email', 'admin@erp.test')->first();
        $finance = User::where('email', 'staff.finance@erp.test')->first();

        Notification::assertSentTo([$admin, $finance], SystemNotification::class);
    }

    public function test_missing_journal_mapping_notifies_admin_and_keeps_payment_pending(): void
    {
        [$order] = $this->makeOrder(qty: 2);

        JournalMapping::query()->where('account_key', 'kas_bank')->delete();

        Notification::fake();

        $this->postJson('/webhooks/midtrans', $this->signedPayload(
            $order->order_number, Payment::STATUS_SETTLEMENT, '50000.00',
        ))->assertStatus(204);

        // Seluruh cascade rollback (atomic): payment pending, SO confirmed,
        // tanpa movement out & tanpa jurnal.
        $this->assertSame(Payment::STATUS_PENDING, Payment::query()->sole()->status);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
        $this->assertSame(0, StockMovement::where('type', StockMovement::TYPE_OUT)->count());
        $this->assertSame(0, JournalEntry::count());

        Notification::assertSentTo(
            [User::where('email', 'admin@erp.test')->first()],
            SystemNotification::class,
        );
    }
}
