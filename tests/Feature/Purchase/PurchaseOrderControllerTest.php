<?php

namespace Tests\Feature\Purchase;

use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\JournalMappingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PurchaseOrderControllerTest extends TestCase
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

    /** Helper: PO + 1 item utuh untuk transisi status. */
    private function makeOrder(string $status): array
    {
        $product = Product::factory()->create(['stock_qty' => 0]);
        $order = PurchaseOrder::factory()->create([
            'status' => $status,
            'subtotal' => 100_000,
            'grand_total' => 100_000,
        ]);
        PurchaseOrderItem::query()->create([
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 10,
            'unit_cost' => 10_000,
            'subtotal' => 100_000,
        ]);

        return [$order, $product];
    }

    public function test_index_renders_purchase_orders_page(): void
    {
        $this->actingAsRole('admin');

        PurchaseOrder::factory()->count(3)->create();

        $this->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('purchase-orders/index')
                    ->has('purchaseOrders.data', 3),
            );
    }

    public function test_index_searches_by_po_number_and_vendor_name(): void
    {
        $this->actingAsRole('admin');

        $vendor = Vendor::factory()->create(['name' => 'Toko Sumber Jaya']);
        PurchaseOrder::factory()->create(['po_number' => 'PO-202608-0001', 'vendor_id' => $vendor->id]);
        PurchaseOrder::factory()->create(['po_number' => 'PO-202608-0002']);

        $this->get(route('purchase-orders.index', ['q' => 'PO-202608-0001']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('purchaseOrders.total', 1)
                ->where('purchaseOrders.data.0.po_number', 'PO-202608-0001'));

        $this->get(route('purchase-orders.index', ['q' => 'sumber jaya']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('purchaseOrders.total', 1)
                ->where('purchaseOrders.data.0.vendor.name', 'Toko Sumber Jaya'));
    }

    public function test_index_filters_by_status(): void
    {
        $this->actingAsRole('admin');

        PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_ORDERED]);
        PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_DRAFT]);

        $this->get(route('purchase-orders.index', ['status' => PurchaseOrder::STATUS_ORDERED]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('purchaseOrders.total', 1)
                ->where('purchaseOrders.data.0.status', PurchaseOrder::STATUS_ORDERED));
    }

    public function test_create_renders_form_with_active_vendors_and_products(): void
    {
        $this->actingAsRole('staff_gudang');

        Vendor::factory()->create(['name' => 'Vendor Aktif']);
        Vendor::factory()->create(['name' => 'Vendor Nonaktif', 'is_active' => false]);
        Product::factory()->create(['name' => 'Produk Aktif']);
        Product::factory()->create(['name' => 'Produk Nonaktif', 'is_active' => false]);

        $this->get(route('purchase-orders.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('purchase-orders/create')
                    ->has('vendors', 1)
                    ->has('products', 1),
            );
    }

    public function test_store_creates_draft_po_with_items(): void
    {
        $this->actingAsRole('staff_gudang');
        Notification::fake();

        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['cost_price' => 5000]);

        $response = $this->post(route('purchase-orders.store'), [
            'vendor_id' => $vendor->id,
            'order_date' => '2026-08-18',
            'tax' => 5000,
            'items' => [
                ['product_id' => $product->id, 'qty' => 4, 'unit_cost' => 6000],
            ],
        ]);

        $order = PurchaseOrder::query()->first();

        $response->assertRedirect(route('purchase-orders.show', $order));
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertSame('PO-202608-0001', $order->po_number);
        $this->assertSame(24000.0, (float) $order->subtotal);
        $this->assertSame(29000.0, (float) $order->grand_total);

        $item = $order->items()->first();
        $this->assertSame(6000.0, (float) $item->unit_cost); // snapshot harga input
    }

    public function test_store_rejects_fraction_qty_for_whole_unit(): void
    {
        $this->actingAsRole('staff_gudang');

        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create();

        $this->from(route('purchase-orders.create'))
            ->post(route('purchase-orders.store'), [
                'vendor_id' => $vendor->id,
                'order_date' => '2026-08-18',
                'tax' => 0,
                'items' => [
                    ['product_id' => $product->id, 'qty' => 1.5, 'unit_cost' => 1000],
                ],
            ])
            ->assertSessionHasErrors('items.0.qty');

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_show_renders_detail_with_action_flags(): void
    {
        $this->actingAsRole('staff_gudang');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_DRAFT);

        $this->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('purchase-orders/show')
                    ->where('order.po_number', $order->po_number)
                    ->has('order.items', 1)
                    ->where('canOrder', true)
                    ->where('canReceive', false)
                    ->where('canCancel', true)
                    ->where('canRecordInvoice', false)
                    ->where('canPay', false),
            );
    }

    public function test_show_flags_invoice_form_for_finance_on_received_po(): void
    {
        $this->actingAsRole('staff_finance');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_RECEIVED);

        $this->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('canRecordInvoice', true)
                    ->where('canOrder', false),
            );
    }

    /**
     * Regression: relasi invoice vendor harus terkirim sebagai key "invoice".
     * Dulu relasi bernama "vendorInvoice" yang di-serialize snake_case
     * ("vendor_invoice") sehingga FE (membaca order.vendorInvoice) tidak
     * pernah menerima datanya — section invoice & pembayaran tak pernah
     * tampil di halaman detail PO.
     */
    public function test_show_includes_vendor_invoice_and_payments_for_finance(): void
    {
        $this->actingAsRole('staff_finance');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_RECEIVED);
        $invoice = VendorInvoice::factory()->for($order)->create(['amount' => 50_000]);
        VendorPayment::factory()->for($invoice)->create(['amount' => 20_000]);

        $this->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('order.invoice.vendor_invoice_number', $invoice->vendor_invoice_number)
                    ->where('order.invoice.status', VendorInvoice::STATUS_UNPAID)
                    ->has('order.invoice.payments', 1)
                    ->where('canPay', true),
            );
    }

    public function test_mark_ordered_transitions_draft_po(): void
    {
        $this->actingAsRole('staff_gudang');
        Notification::fake();

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_DRAFT);

        $this->post(route('purchase-orders.ordered', $order))
            ->assertRedirect(route('purchase-orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(PurchaseOrder::STATUS_ORDERED, $order->fresh()->status);
    }

    public function test_receive_adds_stock_and_posts_journal(): void
    {
        $this->seed([ChartOfAccountSeeder::class, JournalMappingSeeder::class]);
        $this->actingAsRole('staff_gudang');
        Notification::fake();

        [$order, $product] = $this->makeOrder(PurchaseOrder::STATUS_ORDERED);

        $this->post(route('purchase-orders.receive', $order))
            ->assertRedirect(route('purchase-orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);
        $this->assertSame(10.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(1, StockMovement::where('reference_type', 'purchase_order')->count());
        $this->assertSame(1, JournalEntry::where('source', JournalEntry::SOURCE_PURCHASE_RECEIVED)->count());
    }

    public function test_receive_rejected_when_po_not_ordered(): void
    {
        $this->actingAsRole('staff_gudang');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_DRAFT);

        $this->post(route('purchase-orders.receive', $order))
            ->assertRedirect(route('purchase-orders.show', $order))
            ->assertSessionHas('error');

        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->fresh()->status);
    }

    public function test_cancel_draft_po(): void
    {
        $this->actingAsRole('admin');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_DRAFT);

        $this->post(route('purchase-orders.cancel', $order))
            ->assertRedirect(route('purchase-orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(PurchaseOrder::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_cancel_rejected_for_received_po(): void
    {
        $this->actingAsRole('admin');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_RECEIVED);

        $this->post(route('purchase-orders.cancel', $order))
            ->assertSessionHas('error');

        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);
    }

    public function test_full_flow_invoice_then_payment_marks_po_paid(): void
    {
        $this->seed([ChartOfAccountSeeder::class, JournalMappingSeeder::class]);
        $this->actingAsRole('staff_finance');
        Notification::fake();

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_RECEIVED);

        $this->post(route('vendor-invoices.store', $order), [
            'vendor_invoice_number' => 'INV/VD/2026/001',
            'invoice_date' => '2026-08-18',
            'amount' => 100_000,
        ])->assertRedirect(route('purchase-orders.show', $order))->assertSessionHas('success');

        $invoice = VendorInvoice::query()->first();
        $this->assertSame(VendorInvoice::STATUS_UNPAID, $invoice->status);

        $this->post(route('vendor-invoices.payments.store', $invoice), [
            'amount' => 100_000,
            'method' => 'bank_transfer',
            'reference_no' => 'TRF-2026-001',
            'paid_at' => '2026-08-18',
        ])->assertRedirect(route('purchase-orders.show', $order))->assertSessionHas('success');

        $this->assertSame(VendorInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(PurchaseOrder::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(1, JournalEntry::where('source', JournalEntry::SOURCE_PURCHASE_PAYMENT)->count());
    }

    public function test_payment_over_remaining_is_rejected(): void
    {
        $this->actingAsRole('staff_finance');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_RECEIVED);
        $invoice = VendorInvoice::factory()->for($order)->create(['amount' => 50_000]);

        $this->from(route('purchase-orders.show', $order))
            ->post(route('vendor-invoices.payments.store', $invoice), [
                'amount' => 60_000,
                'method' => 'cash',
                'paid_at' => '2026-08-18',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0.0, (float) $invoice->fresh()->amount_paid);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('purchase-orders.index'))->assertRedirect(route('login'));
        $this->post(route('purchase-orders.store'), [])->assertRedirect(route('login'));
    }

    public function test_staff_finance_is_forbidden_on_warehouse_actions(): void
    {
        $this->actingAsRole('staff_finance');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_ORDERED);

        $this->get(route('purchase-orders.create'))->assertForbidden();
        $this->post(route('purchase-orders.store'), [])->assertForbidden();
        $this->post(route('purchase-orders.receive', $order))->assertForbidden();
        $this->post(route('purchase-orders.cancel', $order))->assertForbidden();

        // Index/show tetap boleh (finance mencatat invoice dari halaman PO).
        $this->get(route('purchase-orders.index'))->assertOk();
        $this->get(route('purchase-orders.show', $order))->assertOk();
    }

    public function test_staff_gudang_is_forbidden_on_finance_actions(): void
    {
        $this->actingAsRole('staff_gudang');

        [$order] = $this->makeOrder(PurchaseOrder::STATUS_RECEIVED);
        $invoice = VendorInvoice::factory()->for($order)->create(['amount' => 50_000]);

        $this->post(route('vendor-invoices.store', $order), [
            'vendor_invoice_number' => 'X',
            'invoice_date' => '2026-08-18',
            'amount' => 50_000,
        ])->assertForbidden();

        $this->post(route('vendor-invoices.payments.store', $invoice), [
            'amount' => 50_000,
            'method' => 'cash',
            'paid_at' => '2026-08-18',
        ])->assertForbidden();
    }
}
