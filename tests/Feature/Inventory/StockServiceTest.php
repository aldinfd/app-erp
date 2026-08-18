<?php

namespace Tests\Feature\Inventory;

use App\Events\LowStockDetected;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
    }

    /** Helper: produk dengan reorder point & stok awal yang ditentukan. */
    private function makeProduct(float $reorderPoint, float $stockQty = 0): Product
    {
        return Product::factory()->create([
            'reorder_point' => $reorderPoint,
            'stock_qty' => $stockQty,
        ]);
    }

    public function test_add_increases_stock_and_records_movement(): void
    {
        $product = $this->makeProduct(reorderPoint: 10);

        $movement = $this->stockService->add($product, 25, note: 'Barang masuk awal');

        $movement = $movement->fresh();
        $product = $product->fresh();

        $this->assertSame(25.0, (float) $product->stock_qty);
        $this->assertSame(StockMovement::TYPE_IN, $movement->type);
        $this->assertSame(25.0, (float) $movement->qty);
        $this->assertSame(0.0, (float) $movement->before_qty);
        $this->assertSame(25.0, (float) $movement->after_qty);
        $this->assertSame('Barang masuk awal', $movement->note);
    }

    public function test_add_records_reference(): void
    {
        $product = $this->makeProduct(reorderPoint: 0);

        $movement = $this->stockService->add($product, 5, 'purchase_order', 42);

        $this->assertSame('purchase_order', $movement->reference_type);
        $this->assertSame(42, $movement->reference_id);
    }

    public function test_add_rejects_zero_or_negative_qty(): void
    {
        $product = $this->makeProduct(reorderPoint: 0);

        $this->expectException(InvalidArgumentException::class);

        $this->stockService->add($product, 0);
    }

    public function test_deduct_decreases_stock_and_records_negative_movement(): void
    {
        $product = $this->makeProduct(reorderPoint: 0, stockQty: 10);

        $movement = $this->stockService->deduct($product, 4, 'sales_order', 7);

        $movement = $movement->fresh();
        $product = $product->fresh();

        $this->assertSame(6.0, (float) $product->stock_qty);
        $this->assertSame(StockMovement::TYPE_OUT, $movement->type);
        $this->assertSame(-4.0, (float) $movement->qty);
        $this->assertSame(10.0, (float) $movement->before_qty);
        $this->assertSame(6.0, (float) $movement->after_qty);
        $this->assertSame('sales_order', $movement->reference_type);
        $this->assertSame(7, $movement->reference_id);
    }

    public function test_deduct_to_exact_zero_is_allowed(): void
    {
        // reorder_point 0 + stok habis = crossing — fake event agar
        // listener notifikasi tidak butuh data role/user.
        Event::fake(LowStockDetected::class);

        $product = $this->makeProduct(reorderPoint: 0, stockQty: 5);

        $this->stockService->deduct($product, 5);

        $this->assertSame(0.0, (float) $product->fresh()->stock_qty);
    }

    public function test_deduct_insufficient_stock_rolls_back_everything(): void
    {
        $product = $this->makeProduct(reorderPoint: 0, stockQty: 3);

        try {
            $this->stockService->deduct($product, 10);
            $this->fail('InsufficientStockException seharusnya dilempar.');
        } catch (InsufficientStockException $exception) {
            $this->assertStringContainsString($product->sku, $exception->getMessage());
        }

        $this->assertSame(3.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_adjust_sets_stock_to_counted_qty_with_note_and_user(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(reorderPoint: 0, stockQty: 12);

        $movement = $this->stockService->adjust($product, 9, 'Hasil opname: selisih 3 rusak', $user);

        $movement = $movement->fresh();
        $product = $product->fresh();

        $this->assertSame(9.0, (float) $product->stock_qty);
        $this->assertSame(StockMovement::TYPE_ADJUST, $movement->type);
        $this->assertSame(-3.0, (float) $movement->qty);
        $this->assertSame(12.0, (float) $movement->before_qty);
        $this->assertSame(9.0, (float) $movement->after_qty);
        $this->assertSame('stock_opname', $movement->reference_type);
        $this->assertSame($user->id, $movement->user_id);
        $this->assertSame('Hasil opname: selisih 3 rusak', $movement->note);
    }

    public function test_adjust_upward_records_positive_delta(): void
    {
        $product = $this->makeProduct(reorderPoint: 0, stockQty: 2);

        $movement = $this->stockService->adjust($product, 5, 'Barang ditemukan di rak lain');

        $this->assertSame(3.0, (float) $movement->qty);
        $this->assertSame(5.0, (float) $product->fresh()->stock_qty);
    }

    public function test_adjust_with_same_qty_returns_null_without_movement(): void
    {
        $product = $this->makeProduct(reorderPoint: 0, stockQty: 7);

        $movement = $this->stockService->adjust($product, 7, 'Tidak ada selisih');

        $this->assertNull($movement);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_adjust_requires_note(): void
    {
        $product = $this->makeProduct(reorderPoint: 0, stockQty: 1);

        $this->expectException(InvalidArgumentException::class);

        $this->stockService->adjust($product, 5, '  ');
    }

    public function test_stock_qty_always_equals_sum_of_movements(): void
    {
        $product = $this->makeProduct(reorderPoint: 0);

        $this->stockService->add($product, 100);
        $this->stockService->deduct($product, 30);
        $this->stockService->add($product, 12.5);
        $this->stockService->deduct($product, 0.5);
        $this->stockService->adjust($product, 50, 'Opname tengah bulan');

        $sum = (float) StockMovement::query()->where('product_id', $product->id)->sum('qty');

        $this->assertSame(50.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(50.0, $sum);
        $this->assertSame(5, StockMovement::count());
    }

    public function test_low_stock_notification_sent_on_crossing_reorder_point(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $gudang = User::factory()->create();
        $gudang->assignRole('staff_gudang');
        $finance = User::factory()->create();
        $finance->assignRole('staff_finance');

        $product = $this->makeProduct(reorderPoint: 10, stockQty: 15);

        $this->stockService->deduct($product, 5);

        Notification::assertSentTo(
            [$admin, $gudang],
            SystemNotification::class,
            fn (SystemNotification $notification) => str_contains($notification->title, $product->name),
        );
        Notification::assertNotSentTo($finance, SystemNotification::class);
    }

    public function test_no_duplicate_notification_while_staying_below_reorder_point(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = $this->makeProduct(reorderPoint: 10, stockQty: 15);

        $this->stockService->deduct($product, 5); // 15 -> 10: crossing, kirim
        $this->stockService->deduct($product, 3); // 10 -> 7: masih di bawah, jangan kirim lagi

        Notification::assertSentTo($admin, SystemNotification::class, 1);
    }

    public function test_no_notification_when_stock_stays_above_reorder_point(): void
    {
        Notification::fake();

        $product = $this->makeProduct(reorderPoint: 10, stockQty: 20);

        $this->stockService->deduct($product, 5); // 20 -> 15: masih di atas

        Notification::assertNothingSent();
    }

    public function test_notification_sent_again_after_restock_and_new_crossing(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = $this->makeProduct(reorderPoint: 10, stockQty: 15);

        $this->stockService->deduct($product, 5); // 15 -> 10: kirim
        $this->stockService->add($product, 20);   // 10 -> 30: pulih
        $this->stockService->deduct($product, 25); // 30 -> 5: crossing baru, kirim lagi

        Notification::assertSentTo($admin, SystemNotification::class, 2);
    }

    public function test_rollback_on_failure_does_not_send_notification(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = $this->makeProduct(reorderPoint: 2, stockQty: 5);

        try {
            $this->stockService->deduct($product, 10); // gagal: stok kurang
        } catch (InsufficientStockException) {
            // abaikan — fokus cek tidak ada notifikasi
        }

        Notification::assertNothingSent();
    }
}
