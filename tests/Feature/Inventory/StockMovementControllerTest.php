<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StockMovementControllerTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
    }

    private function actingAsRole(string $role): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_index_renders_movements_page(): void
    {
        $this->actingAsRole('admin');

        $product = Product::factory()->create();
        $this->stockService->add($product, 10);

        $this->get(route('stock-movements.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('inventory/stock-movements/index')
                    ->has('movements.data', 1)
                    ->where('movements.data.0.product.sku', $product->sku),
            );
    }

    public function test_index_filters_by_product_search(): void
    {
        $this->actingAsRole('staff_gudang');

        $kaos = Product::factory()->create(['sku' => 'SKU-PK-001', 'name' => 'Kaos Polos']);
        $mouse = Product::factory()->create(['sku' => 'SKU-EL-001', 'name' => 'Mouse Wireless']);
        $this->stockService->add($kaos, 5);
        $this->stockService->add($mouse, 5);

        $this->get(route('stock-movements.index', ['q' => 'kaos']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('movements.total', 1));

        $this->get(route('stock-movements.index', ['q' => 'SKU-EL']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('movements.total', 1));
    }

    public function test_index_filters_by_type_and_date_range(): void
    {
        $this->actingAsRole('admin');

        $product = Product::factory()->create();
        $this->stockService->add($product, 20);
        $this->stockService->deduct($product, 3);

        $this->get(route('stock-movements.index', ['type' => 'out']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('movements.total', 1)
                ->where('movements.data.0.type', 'out'));

        $today = now()->toDateString();
        $kemarin = now()->subDay()->toDateString();

        $this->get(route('stock-movements.index', ['date_from' => $today]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('movements.total', 2));

        $this->get(route('stock-movements.index', ['date_from' => $today, 'date_to' => $kemarin]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('movements.total', 0));
    }

    public function test_staff_finance_cannot_view_stock_movements(): void
    {
        $this->actingAsRole('staff_finance');

        $this->get(route('stock-movements.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('stock-movements.index'))->assertRedirect(route('login'));
    }
}
