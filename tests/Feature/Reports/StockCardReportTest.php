<?php

namespace Tests\Feature\Reports;

use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StockCardReportTest extends TestCase
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

    public function test_stock_card_shows_opening_movements_and_running_balance(): void
    {
        $this->actingAsRole('admin');
        $stockService = app(StockService::class);

        // Rentang eksplisit agar test tidak tergantung posisi tanggal di bulan.
        // Semua tanggal dihitung SEBELUM travelTo — today() dihitung ulang
        // setelah waktu dimundurkan sehingga selisihnya akan saling menumpuk.
        $tenDaysAgo = today()->subDays(10);
        $twoDaysAgo = today()->subDays(2);
        $yesterday = today()->subDays(1);
        $from = today()->subDays(5)->toDateString();
        $to = today()->toDateString();

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        // Mutasi produk lain tidak boleh bocor ke kartu stok produk ini.
        $stockService->add($otherProduct, 7);

        // Mutasi 10 hari lalu → hanya jadi saldo awal periode.
        $this->travelTo($tenDaysAgo);
        $stockService->add($product, 10);

        // Mutasi dalam periode: +5 lalu −2, saldo berjalan 15 lalu 13.
        $this->travelTo($twoDaysAgo);
        $stockService->add($product, 5, 'purchase_order', 1);
        $this->travelTo($yesterday);
        $stockService->deduct($product, 2, 'sales_order', 1);

        $this->travelBack();

        $this->get(route('reports.stock-card', ['product' => $product->id, 'from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('reports/stock-card')
                ->where('card.product.id', $product->id)
                ->where('card.opening', 10)
                ->has('card.lines', 2)
                ->where('card.lines.0.qty', 5)
                ->where('card.lines.0.balance', 15)
                ->where('card.lines.1.qty', -2)
                ->where('card.lines.1.balance', 13)
                ->where('card.closing', 13)
                ->where('card.total_in', 5)
                ->where('card.total_out', -2));
    }

    public function test_stock_card_defaults_to_first_product(): void
    {
        $this->actingAsRole('admin');

        Product::factory()->create(['name' => 'Produk Awal']);
        Product::factory()->create(['name' => 'Produk Kedua']);

        $this->get(route('reports.stock-card'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('products', 2)
                ->where('card.product.name', 'Produk Awal'));
    }

    public function test_stock_card_pdf_and_excel_downloads(): void
    {
        $this->actingAsRole('staff_finance');

        $product = Product::factory()->create();
        app(StockService::class)->add($product, 3);

        $this->get(route('reports.stock-card.pdf', ['product' => $product->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('reports.stock-card.excel', ['product' => $product->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_staff_gudang_is_forbidden(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('reports.stock-card'))->assertForbidden();
        $this->get(route('reports.stock-card.pdf'))->assertForbidden();
        $this->get(route('reports.stock-card.excel'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('reports.stock-card'))->assertRedirect(route('login'));
    }
}
