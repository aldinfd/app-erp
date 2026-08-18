<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SalesOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_index_renders_sales_orders_page(): void
    {
        $this->actingAsRole('admin');

        SalesOrder::factory()->count(3)->create();

        $this->get(route('sales-orders.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('sales-orders/index')
                    ->has('salesOrders.data', 3),
            );
    }

    public function test_index_searches_by_order_number_and_customer_name(): void
    {
        $this->actingAsRole('admin');

        $customer = Customer::factory()->create(['name' => 'Budi Santoso']);
        SalesOrder::factory()->create(['order_number' => 'SO-202608-0001', 'customer_id' => $customer->id]);
        SalesOrder::factory()->create(['order_number' => 'SO-202608-0002']);

        // Cari by nomor order.
        $this->get(route('sales-orders.index', ['q' => 'SO-202608-0001']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('salesOrders.total', 1)
                ->where('salesOrders.data.0.order_number', 'SO-202608-0001'));

        // Cari by nama customer.
        $this->get(route('sales-orders.index', ['q' => 'budi']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('salesOrders.total', 1)
                ->where('salesOrders.data.0.customer.name', 'Budi Santoso'));
    }

    public function test_index_filters_by_status(): void
    {
        $this->actingAsRole('admin');

        SalesOrder::factory()->create(['status' => SalesOrder::STATUS_CONFIRMED]);
        SalesOrder::factory()->create(['status' => SalesOrder::STATUS_PAID]);

        $this->get(route('sales-orders.index', ['status' => SalesOrder::STATUS_PAID]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('salesOrders.total', 1)
                ->where('salesOrders.data.0.status', SalesOrder::STATUS_PAID));
    }

    public function test_show_renders_detail_with_items_and_can_cancel(): void
    {
        $this->actingAsRole('staff_finance');

        $order = SalesOrder::factory()->create(['status' => SalesOrder::STATUS_CONFIRMED]);
        $invoice = Invoice::factory()->for($order)->create();
        Payment::factory()->for($invoice)->create(['gateway_ref' => $order->order_number]);

        $product = Product::factory()->create();
        SalesOrderItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);

        $this->get(route('sales-orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('sales-orders/show')
                    ->where('order.order_number', $order->order_number)
                    ->has('order.items', 1)
                    ->has('order.invoice')
                    ->has('order.payments', 1)
                    ->where('canCancel', true),
            );
    }

    public function test_show_marks_order_with_paid_payment_not_cancellable(): void
    {
        $this->actingAsRole('admin');

        $order = SalesOrder::factory()->create(['status' => SalesOrder::STATUS_PAID]);
        $invoice = Invoice::factory()->for($order)->paid()->create();
        Payment::factory()->for($invoice)->paid()->create(['gateway_ref' => $order->order_number]);

        $this->get(route('sales-orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('canCancel', false));
    }

    public function test_cancel_voids_invoice_and_cancels_pending_payment(): void
    {
        $this->actingAsRole('admin');

        $order = SalesOrder::factory()->create(['status' => SalesOrder::STATUS_CONFIRMED]);
        $invoice = Invoice::factory()->for($order)->create();
        $payment = Payment::factory()->for($invoice)->create(['gateway_ref' => $order->order_number]);

        $this->post(route('sales-orders.cancel', $order))
            ->assertRedirect(route('sales-orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(SalesOrder::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(Invoice::STATUS_VOID, $invoice->fresh()->status);
        $this->assertSame(Payment::STATUS_CANCEL, $payment->fresh()->status);
    }

    public function test_cancel_rejected_for_paid_order(): void
    {
        $this->actingAsRole('admin');

        $order = SalesOrder::factory()->create(['status' => SalesOrder::STATUS_PAID]);
        $invoice = Invoice::factory()->for($order)->paid()->create();
        Payment::factory()->for($invoice)->paid()->create(['gateway_ref' => $order->order_number]);

        $this->post(route('sales-orders.cancel', $order))
            ->assertRedirect(route('sales-orders.show', $order))
            ->assertSessionHas('error');

        $this->assertSame(SalesOrder::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_cancel_rejected_for_already_cancelled_order(): void
    {
        $this->actingAsRole('admin');

        $order = SalesOrder::factory()->create(['status' => SalesOrder::STATUS_CANCELLED]);

        $this->post(route('sales-orders.cancel', $order))
            ->assertSessionHas('error');

        $this->assertSame(SalesOrder::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('sales-orders.index'))
            ->assertRedirect(route('login'));

        $this->post(route('sales-orders.cancel', 1))
            ->assertRedirect(route('login'));
    }

    public function test_staff_gudang_is_forbidden(): void
    {
        $this->actingAsRole('staff_gudang');

        $order = SalesOrder::factory()->create();

        $this->get(route('sales-orders.index'))->assertForbidden();
        $this->get(route('sales-orders.show', $order))->assertForbidden();
        $this->post(route('sales-orders.cancel', $order))->assertForbidden();
    }
}
