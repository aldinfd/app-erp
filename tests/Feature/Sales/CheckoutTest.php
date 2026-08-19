<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\CheckoutService;
use App\Services\MidtransService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\JournalMappingSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Counter rate limit antar test harus bersih (cache tidak di-refresh
        // oleh RefreshDatabase).
        RateLimiter::clear('checkout');

        // Listener notifikasi order baru memakai scope role spatie — role
        // harus ada (di app asli selalu ada via DatabaseSeeder).
        $this->seed(RoleSeeder::class);
    }

    /** Helper: produk aktif berstok cukup. */
    private function makeProduct(float $stockQty = 100): Product
    {
        return Product::factory()->create([
            'stock_qty' => $stockQty,
            'selling_price' => 25000,
        ]);
    }

    /**
     * Helper: payload checkout guest valid.
     *
     * @param  array<int, array{product_id: int, qty: float}>  $items
     */
    private function checkoutPayload(array $items, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Mawar No. 1, Jakarta',
            'items' => $items,
        ], $overrides);
    }

    public function test_guest_checkout_creates_confirmed_order_with_invoice_and_pending_payment(): void
    {
        $product = $this->makeProduct();
        $fractional = Unit::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'kg', 'allows_fraction' => true]);
        $sugar = Product::factory()->create([
            'unit_id' => $fractional->id,
            'stock_qty' => 50,
            'selling_price' => 15000,
        ]);

        $this->mock(MidtransService::class)
            ->shouldReceive('createSnapTransaction')
            ->once()
            ->andReturn('https://app.sandbox.midtrans.com/snap/pay/test-token');

        $response = $this->post('/checkout', $this->checkoutPayload([
            ['product_id' => $product->id, 'qty' => 2],
            ['product_id' => $sugar->id, 'qty' => 1.5],
        ]));

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/pay/test-token');

        $order = SalesOrder::query()->sole();
        $ym = now()->format('Ym');

        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->status);
        $this->assertSame("SO-{$ym}-0001", $order->order_number);
        $this->assertSame(72500.0, (float) $order->grand_total); // 2×25000 + 1.5×15000
        $this->assertSame(2, $order->items()->count());

        // Snapshot harga: unit_price item = selling_price saat checkout.
        $firstItem = $order->items()->where('product_id', $product->id)->first();
        $this->assertSame(25000.0, (float) $firstItem->unit_price);
        $this->assertSame(50000.0, (float) $firstItem->subtotal);

        $invoice = Invoice::query()->sole();
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(72500.0, (float) $invoice->amount);
        $this->assertSame($order->id, $invoice->sales_order_id);

        $payment = Payment::query()->sole();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame(Payment::METHOD_MIDTRANS, $payment->method);
        $this->assertSame($order->order_number, $payment->gateway_ref);
    }

    public function test_checkout_reuses_existing_customer_by_email(): void
    {
        $product = $this->makeProduct();
        Customer::factory()->create(['email' => 'budi@example.com']);

        $this->mock(MidtransService::class)
            ->shouldReceive('createSnapTransaction')
            ->andReturn('https://pay.example.test/token');

        $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]));

        $this->assertSame(1, Customer::count(), 'Customer lama harus dipakai ulang, bukan dibuat baru.');
        $this->assertSame(
            Customer::where('email', 'budi@example.com')->first()->id,
            SalesOrder::query()->sole()->customer_id,
        );
    }

    /**
     * Request Inertia (XHR) tidak bisa mengikuti 302 lintas domain ke Midtrans
     * (diblokir CORS → network error di browser). Harus 409 + X-Inertia-Location
     * agar client melakukan window.location penuh.
     */
    public function test_inertia_checkout_returns_409_location_header_for_midtrans_redirect(): void
    {
        $product = $this->makeProduct();

        $this->mock(MidtransService::class)
            ->shouldReceive('createSnapTransaction')
            ->once()
            ->andReturn('https://app.sandbox.midtrans.com/snap/pay/test-token');

        $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]), ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://app.sandbox.midtrans.com/snap/pay/test-token');
    }

    public function test_checkout_rejects_when_stock_insufficient(): void
    {
        $product = $this->makeProduct(stockQty: 3);

        $response = $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 5]]));

        $response->assertInvalid(['items.0.qty']);
        $this->assertSame(0, SalesOrder::count(), 'Order tidak boleh tersimpan saat stok kurang.');
    }

    public function test_checkout_rejects_fractional_qty_for_whole_unit(): void
    {
        $product = $this->makeProduct(); // satuan default tidak boleh pecahan

        $response = $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1.5]]));

        $response->assertInvalid(['items.0.qty']);
        $this->assertSame(0, SalesOrder::count());
    }

    public function test_checkout_rejects_inactive_product(): void
    {
        $product = Product::factory()->inactive()->create(['stock_qty' => 10]);

        $response = $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]));

        $response->assertInvalid(['items.0.product_id']);
        $this->assertSame(0, SalesOrder::count());
    }

    public function test_checkout_rejects_invalid_customer_fields(): void
    {
        $product = $this->makeProduct();

        $response = $this->post('/checkout', $this->checkoutPayload(
            [['product_id' => $product->id, 'qty' => 1]],
            ['email' => 'bukan-email'],
        ));

        $response->assertInvalid(['email']);
        $this->assertSame(0, SalesOrder::count());
    }

    public function test_snap_failure_keeps_order_and_redirects_to_finish_page(): void
    {
        $product = $this->makeProduct();

        // Mock gagal deterministik — tidak bergantung ada/tidaknya server key di env.
        $this->mock(MidtransService::class)
            ->shouldReceive('createSnapTransaction')
            ->andThrow(new \RuntimeException('gateway down'));

        $response = $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]));

        $order = SalesOrder::query()->sole();
        $response->assertRedirect(route('payment.finish', ['order_id' => $order->order_number]));
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->status, 'Order tetap tersimpan meski Snap gagal.');
    }

    public function test_new_order_notification_sent_to_admin_and_staff_finance(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        Notification::fake();

        $product = $this->makeProduct();

        $this->mock(MidtransService::class)
            ->shouldReceive('createSnapTransaction')
            ->andReturn('https://pay.example.test/token');

        $this->post('/checkout', $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]));

        $admin = User::where('email', 'admin@erp.test')->first();
        $finance = User::where('email', 'staff.finance@erp.test')->first();
        $gudang = User::where('email', 'staff.gudang@erp.test')->first();

        Notification::assertSentTo([$admin, $finance], SystemNotification::class);
        Notification::assertNotSentTo($gudang, SystemNotification::class);
    }

    public function test_checkout_is_rate_limited_after_10_attempts(): void
    {
        $product = $this->makeProduct();

        $this->mock(MidtransService::class)
            ->shouldReceive('createSnapTransaction')
            ->andReturn('https://pay.example.test/token');

        $payload = $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/checkout', $payload);
        }

        $this->post('/checkout', $payload)->assertStatus(429);
    }

    /**
     * Helper: order pending via jalur checkout asli (untuk tes halaman struk).
     */
    private function makePendingOrder(Product $product): SalesOrder
    {
        return app(CheckoutService::class)->createOrder(
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '08123456789', 'address' => 'Jl. Mawar No. 1'],
            [['product_id' => $product->id, 'qty' => 1]],
        );
    }

    public function test_finish_page_syncs_pending_payment_from_midtrans(): void
    {
        // Cascade paid (stok + jurnal) butuh CoA + mapping akun.
        $this->seed([ChartOfAccountSeeder::class, JournalMappingSeeder::class]);

        $product = $this->makeProduct();
        $order = $this->makePendingOrder($product);

        // Webhook belum sampai (mis. dev lokal) — struk menarik status sendiri.
        $this->mock(MidtransService::class)
            ->shouldReceive('getTransactionStatus')
            ->once()
            ->andReturn([
                'order_id' => $order->order_number,
                'transaction_status' => Payment::STATUS_SETTLEMENT,
                'gross_amount' => '25000.00',
            ]);

        $this->get(route('payment.finish', ['order_id' => $order->order_number]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('order.order_number', $order->order_number)
                ->where('order.status', SalesOrder::STATUS_PAID)
                ->where('order.payment_status', Payment::STATUS_SETTLEMENT));

        $this->assertSame(Payment::STATUS_SETTLEMENT, Payment::query()->sole()->status);
        $this->assertSame(99.0, (float) $product->fresh()->stock_qty);
    }

    public function test_finish_page_skips_sync_when_payment_is_not_pending(): void
    {
        $product = $this->makeProduct();
        $order = $this->makePendingOrder($product);

        Payment::query()->sole()->update([
            'status' => Payment::STATUS_SETTLEMENT,
            'paid_at' => now(),
        ]);

        $this->mock(MidtransService::class)
            ->shouldReceive('getTransactionStatus')
            ->never();

        $this->get(route('payment.finish', ['order_id' => $order->order_number]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('order.payment_status', Payment::STATUS_SETTLEMENT));
    }
}
