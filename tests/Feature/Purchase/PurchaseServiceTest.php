<?php

namespace Tests\Feature\Purchase;

use App\Models\JournalEntry;
use App\Models\JournalMapping;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use App\Notifications\SystemNotification;
use App\Services\PurchaseService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\JournalMappingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseService = app(PurchaseService::class);

        $this->seed([ChartOfAccountSeeder::class, JournalMappingSeeder::class]);
        $this->seed(RoleSeeder::class);

        // Notifikasi in-app + email tidak benar-benar dikirim saat test service.
        Notification::fake();
    }

    /**
     * Helper: PO ordered dengan 1 item produk (stok awal $stockQty).
     */
    private function makeOrderedOrder(float $qty, float $unitCost, float $stockQty = 0): array
    {
        $product = Product::factory()->create(['stock_qty' => $stockQty]);
        $order = PurchaseOrder::factory()->ordered()->create([
            'subtotal' => round($qty * $unitCost, 2),
            'grand_total' => round($qty * $unitCost, 2),
        ]);
        PurchaseOrderItem::query()->create([
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'subtotal' => round($qty * $unitCost, 2),
        ]);

        return [$order, $product];
    }

    public function test_receive_adds_stock_creates_movement_and_posts_journal(): void
    {
        [$order, $product] = $this->makeOrderedOrder(qty: 5, unitCost: 20_000, stockQty: 10);
        $user = User::factory()->create();

        $changed = $this->purchaseService->receive($order, $user);

        $this->assertTrue($changed);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);
        $this->assertSame(15.0, (float) $product->fresh()->stock_qty);

        $movement = StockMovement::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertSame(StockMovement::TYPE_IN, $movement->type);
        $this->assertSame(5.0, (float) $movement->qty);
        $this->assertSame('purchase_order', $movement->reference_type);
        $this->assertSame($order->id, $movement->reference_id);
        $this->assertSame($user->id, $movement->user_id);

        $entry = JournalEntry::query()->where('reference_type', 'purchase_order')->where('reference_id', $order->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::SOURCE_PURCHASE_RECEIVED, $entry->source);

        $lines = $entry->lines()->get();
        $this->assertSame(2, $lines->count());
        $this->assertSame((float) $order->grand_total, (float) $lines->sum('debit'));
        $this->assertSame((float) $order->grand_total, (float) $lines->sum('credit'));
    }

    public function test_receive_rejects_po_that_is_not_ordered(): void
    {
        [$order, $product] = $this->makeOrderedOrder(qty: 5, unitCost: 20_000);
        $order->update(['status' => PurchaseOrder::STATUS_DRAFT]);

        $changed = $this->purchaseService->receive($order);

        $this->assertFalse($changed);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->fresh()->status);
        $this->assertSame(0.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_receive_rolls_back_everything_when_journal_mapping_missing(): void
    {
        JournalMapping::query()
            ->where('transaction_type', JournalMapping::TRANSACTION_TYPE_PURCHASE_RECEIVED)
            ->delete();

        [$order, $product] = $this->makeOrderedOrder(qty: 3, unitCost: 15_000, stockQty: 2);

        try {
            $this->purchaseService->receive($order);
            $this->fail('receive harus melempar RuntimeException saat mapping hilang.');
        } catch (RuntimeException) {
            // rollback atomic: stok, movement, status PO semuanya utuh.
        }

        $this->assertSame(PurchaseOrder::STATUS_ORDERED, $order->fresh()->status);
        $this->assertSame(2.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_mark_ordered_moves_draft_to_ordered_and_notifies_warehouse(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $warehouse = User::factory()->create();
        $warehouse->assignRole('staff_gudang');
        $finance = User::factory()->create();
        $finance->assignRole('staff_finance');

        $order = PurchaseOrder::factory()->create(); // draft

        $changed = $this->purchaseService->markOrdered($order);

        $this->assertTrue($changed);
        $this->assertSame(PurchaseOrder::STATUS_ORDERED, $order->fresh()->status);

        Notification::assertSentTo([$admin, $warehouse], SystemNotification::class);
        Notification::assertNotSentTo($finance, SystemNotification::class);
    }

    public function test_receive_notifies_finance_to_record_invoice(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole('staff_finance');

        [$order] = $this->makeOrderedOrder(qty: 1, unitCost: 10_000);

        $this->purchaseService->receive($order);

        Notification::assertSentTo($finance, SystemNotification::class);
    }

    public function test_pay_full_marks_invoice_and_po_paid_and_posts_journal(): void
    {
        [$order] = $this->makeOrderedOrder(qty: 5, unitCost: 20_000);
        $this->purchaseService->receive($order);
        $invoice = $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/001',
            'invoice_date' => today()->toDateString(),
            'amount' => 100_000,
        ]);

        $payment = $this->purchaseService->pay($invoice, [
            'amount' => 100_000,
            'method' => VendorPayment::METHOD_BANK_TRANSFER,
            'reference_no' => 'TRF-001',
            'paid_at' => now()->toDateString(),
        ]);

        $this->assertSame(VendorInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(100_000.0, (float) $invoice->fresh()->amount_paid);
        $this->assertSame(PurchaseOrder::STATUS_PAID, $order->fresh()->status);

        $entry = JournalEntry::query()->where('reference_type', 'vendor_payment')->where('reference_id', $payment->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::SOURCE_PURCHASE_PAYMENT, $entry->source);
        $this->assertSame(100_000.0, (float) $entry->lines()->sum('debit'));
        $this->assertSame(100_000.0, (float) $entry->lines()->sum('credit'));
    }

    public function test_pay_partial_keeps_po_received_until_settled(): void
    {
        [$order] = $this->makeOrderedOrder(qty: 5, unitCost: 20_000);
        $this->purchaseService->receive($order);
        $invoice = $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/002',
            'invoice_date' => today()->toDateString(),
            'amount' => 100_000,
        ]);

        $this->purchaseService->pay($invoice, ['amount' => 40_000, 'method' => VendorPayment::METHOD_CASH, 'paid_at' => now()->toDateString()]);

        $this->assertSame(VendorInvoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);

        $this->purchaseService->pay($invoice, ['amount' => 60_000, 'method' => VendorPayment::METHOD_CASH, 'paid_at' => now()->toDateString()]);

        $this->assertSame(VendorInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(PurchaseOrder::STATUS_PAID, $order->fresh()->status);
        // Dua cicilan → dua jurnal purchase_payment, masing-masing balance.
        $this->assertSame(2, JournalEntry::query()->where('source', JournalEntry::SOURCE_PURCHASE_PAYMENT)->count());
    }

    public function test_pay_rejects_overpayment(): void
    {
        [$order] = $this->makeOrderedOrder(qty: 1, unitCost: 50_000);
        $this->purchaseService->receive($order);
        $invoice = $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/003',
            'invoice_date' => today()->toDateString(),
            'amount' => 50_000,
        ]);

        $this->expectException(ValidationException::class);
        $this->purchaseService->pay($invoice, ['amount' => 60_000, 'method' => VendorPayment::METHOD_BANK_TRANSFER, 'paid_at' => now()->toDateString()]);
    }

    public function test_pay_rejects_already_paid_invoice(): void
    {
        [$order] = $this->makeOrderedOrder(qty: 1, unitCost: 50_000);
        $this->purchaseService->receive($order);
        $invoice = $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/004',
            'invoice_date' => today()->toDateString(),
            'amount' => 50_000,
        ]);
        $this->purchaseService->pay($invoice, ['amount' => 50_000, 'method' => VendorPayment::METHOD_CASH, 'paid_at' => now()->toDateString()]);

        $this->expectException(ValidationException::class);
        $this->purchaseService->pay($invoice, ['amount' => 1, 'method' => VendorPayment::METHOD_CASH, 'paid_at' => now()->toDateString()]);
    }

    public function test_record_vendor_invoice_requires_received_status(): void
    {
        $order = PurchaseOrder::factory()->ordered()->create();

        $this->expectException(ValidationException::class);
        $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/005',
            'invoice_date' => today()->toDateString(),
            'amount' => 10_000,
        ]);
    }

    public function test_record_vendor_invoice_rejects_duplicate_per_po(): void
    {
        [$order] = $this->makeOrderedOrder(qty: 1, unitCost: 10_000);
        $this->purchaseService->receive($order);
        $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/006',
            'invoice_date' => today()->toDateString(),
            'amount' => 10_000,
        ]);

        $this->expectException(ValidationException::class);
        $this->purchaseService->recordVendorInvoice($order, [
            'vendor_invoice_number' => 'INV/VD/007',
            'invoice_date' => today()->toDateString(),
            'amount' => 10_000,
        ]);
    }

    public function test_create_builds_draft_po_with_price_snapshot(): void
    {
        $product = Product::factory()->create(['cost_price' => 8_000]);

        $order = $this->purchaseService->create(
            ['vendor_id' => Vendor::factory()->create()->id, 'order_date' => '2026-08-18', 'tax' => 1000],
            [['product_id' => $product->id, 'qty' => 3, 'unit_cost' => 7500]],
        );

        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertSame('PO-'.now()->format('Ym').'-0001', $order->po_number);
        $this->assertSame(22500.0, (float) $order->subtotal);
        $this->assertSame(1000.0, (float) $order->tax);
        $this->assertSame(23500.0, (float) $order->grand_total);

        $item = $order->items()->first();
        $this->assertSame(7500.0, (float) $item->unit_cost); // harga input, bukan cost_price produk
    }

    public function test_create_rejects_fraction_qty_for_whole_unit(): void
    {
        $product = Product::factory()->create(); // satuan default tidak pecahan

        $this->expectException(ValidationException::class);
        $this->purchaseService->create(
            ['vendor_id' => Vendor::factory()->create()->id, 'order_date' => '2026-08-18', 'tax' => 0],
            [['product_id' => $product->id, 'qty' => 2.5, 'unit_cost' => 1000]],
        );
    }
}
