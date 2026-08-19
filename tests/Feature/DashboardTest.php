<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\VendorInvoice;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAsRole('admin');

        $response = $this->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_admin_receives_all_widgets()
    {
        $this->actingAsRole('admin');

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('low_stock')
            ->where('po_waiting_goods', 0)
            ->has('monthly_sales')
            ->has('pending_sales')
            ->where('po_waiting_payment', 0)
            ->has('sales_chart', 6));
    }

    public function test_staff_gudang_receives_warehouse_widgets_only()
    {
        $this->actingAsRole('staff_gudang');

        $response = $this->get(route('dashboard'));

        // Widget finance tidak dikirim sama sekali (bukan sekadar disembunyikan).
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('low_stock')
            ->where('po_waiting_goods', 0)
            ->missing('monthly_sales')
            ->missing('pending_sales')
            ->missing('po_waiting_payment')
            ->missing('sales_chart'));
    }

    public function test_staff_finance_receives_finance_widgets_only()
    {
        $this->actingAsRole('staff_finance');

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('low_stock')
            ->missing('po_waiting_goods')
            ->has('monthly_sales')
            ->has('pending_sales')
            ->where('po_waiting_payment', 0)
            ->has('sales_chart', 6));
    }

    public function test_widget_numbers_match_real_data()
    {
        $this->actingAsRole('admin');
        $stockService = app(StockService::class);

        // Stok menipis: 2 produk (0 <= 2 dan 3 <= 5), 1 produk aman (10 > 5).
        // stock_qty hanya bisa diisi lewat StockService (tidak fillable).
        Product::factory()->create(['reorder_point' => 2]);
        $almostEmpty = Product::factory()->create(['reorder_point' => 5]);
        $stockService->add($almostEmpty, 3);
        $stockService->add(Product::factory()->create(['reorder_point' => 5]), 10);

        // Sales bulan ini: 1 paid (revenue 150k), 1 confirmed (tertunda),
        // 1 cancelled (tidak dihitung).
        $paidToday = SalesOrder::factory()->create([
            'status' => SalesOrder::STATUS_PAID,
            'order_date' => today(),
            'subtotal' => 150_000,
            'grand_total' => 150_000,
        ]);
        SalesOrder::factory()->create(['order_date' => today()]);
        SalesOrder::factory()->create([
            'status' => SalesOrder::STATUS_CANCELLED,
            'order_date' => today(),
        ]);

        // Order paid bulan lalu → titik ke-5 grafik (indeks 4).
        SalesOrder::factory()->create([
            'status' => SalesOrder::STATUS_PAID,
            'order_date' => today()->subMonth(),
            'subtotal' => 100_000,
            'grand_total' => 100_000,
        ]);

        // Invoice unpaid milik order paid hari ini (factory invoice membuat
        // SO baru sendiri bila tidak dikaitkan — akan ikut terhitung order).
        Invoice::factory()->create(['sales_order_id' => $paidToday->id]);
        PurchaseOrder::factory()->ordered()->create();
        VendorInvoice::factory()->create();

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('low_stock.count', 2)
            ->has('low_stock.products', 2)
            ->where('low_stock.products.0.stock_qty', '0.00')
            ->where('po_waiting_goods', 1)
            ->where('monthly_sales.total_orders', 2)
            ->where('monthly_sales.revenue', 150_000)
            ->where('pending_sales.orders', 1)
            ->where('pending_sales.invoices', 1)
            ->where('po_waiting_payment', 1)
            ->where('sales_chart.4.revenue', 100_000)
            ->where('sales_chart.5.revenue', 150_000)
            ->where('sales_chart.5.month', today()->format('Y-m')));
    }
}
