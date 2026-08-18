<?php

namespace Tests\Feature\Master;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProductTest extends TestCase
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

    public function test_index_renders_products_page(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        Product::factory()->count(3)->create(['unit_id' => $unit->id]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('master/products/index')
                    ->has('products.data', 3),
            );
    }

    public function test_index_searches_by_sku_and_name(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        Product::factory()->create(['sku' => 'SKU-EL-001', 'name' => 'Mouse Wireless', 'unit_id' => $unit->id]);
        Product::factory()->create(['sku' => 'SKU-PK-001', 'name' => 'Kaos Polos', 'unit_id' => $unit->id]);

        $this->get(route('products.index', ['q' => 'SKU-EL']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('products.total', 1));

        $this->get(route('products.index', ['q' => 'kaos']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('products.total', 1));
    }

    public function test_index_filters_by_category_and_status(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        $elektronik = Category::factory()->create(['name' => 'Elektronik']);
        Product::factory()->create(['name' => 'Mouse', 'category_id' => $elektronik->id, 'unit_id' => $unit->id]);
        Product::factory()->inactive()->create(['name' => 'Kaos', 'unit_id' => $unit->id]);

        $this->get(route('products.index', ['category_id' => $elektronik->id]))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('products.total', 1)
                    ->where('products.data.0.name', 'Mouse'),
            );

        $this->get(route('products.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('products.total', 1));

        $this->get(route('products.index', ['status' => 'active']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('products.total', 1));
    }

    public function test_store_creates_product_with_uploaded_image(): void
    {
        Storage::fake('public');
        $this->actingAsRole('staff_gudang');

        $unit = Unit::factory()->create();

        $this->post(route('products.store'), [
            'sku' => 'SKU-TEST-001',
            'name' => 'Produk Tes',
            'category_id' => null,
            'unit_id' => $unit->id,
            'cost_price' => 10000,
            'selling_price' => 15000,
            'reorder_point' => 5,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('produk.jpg'),
        ])->assertRedirect(route('products.index'));

        $product = Product::query()->where('sku', 'SKU-TEST-001')->first();

        $this->assertNotNull($product);
        $this->assertStringContainsString('products/', $product->image_url);
        Storage::disk('public')->assertExists(
            str_replace('/storage/', '', parse_url($product->image_url, PHP_URL_PATH)),
        );
    }

    public function test_store_ignores_stock_qty_from_request(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();

        $this->post(route('products.store'), [
            'sku' => 'SKU-TEST-002',
            'name' => 'Produk Stok',
            'unit_id' => $unit->id,
            'stock_qty' => 999,
            'is_active' => true,
        ])->assertRedirect(route('products.index'));

        $product = Product::query()->where('sku', 'SKU-TEST-002')->first();

        $this->assertSame('0.00', $product->stock_qty);
    }

    public function test_store_rejects_fractional_reorder_point_for_non_fraction_unit(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create(['allows_fraction' => false]);

        $this->post(route('products.store'), [
            'sku' => 'SKU-BULAT-001',
            'name' => 'Produk Bulat',
            'unit_id' => $unit->id,
            'reorder_point' => 10.5,
            'is_active' => true,
        ])->assertSessionHasErrors('reorder_point');

        $this->assertSame(0, Product::count());
    }

    public function test_store_allows_fractional_reorder_point_for_fraction_unit(): void
    {
        $this->actingAsRole('admin');

        $kilogram = Unit::factory()->create(['allows_fraction' => true]);

        $this->post(route('products.store'), [
            'sku' => 'SKU-KG-001',
            'name' => 'Gula Merah',
            'unit_id' => $kilogram->id,
            'reorder_point' => 2.5,
            'is_active' => true,
        ])->assertRedirect(route('products.index'));

        $this->assertSame(2.5, (float) Product::query()->where('sku', 'SKU-KG-001')->value('reorder_point'));
    }

    public function test_store_rejects_duplicate_sku(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        Product::factory()->create(['sku' => 'SKU-DUP', 'unit_id' => $unit->id]);

        $this->post(route('products.store'), [
            'sku' => 'SKU-DUP',
            'name' => 'Duplikat',
            'unit_id' => $unit->id,
        ])->assertSessionHasErrors('sku');

        $this->assertSame(1, Product::count());
    }

    public function test_update_changes_product(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        $this->patch(route('products.update', $product), [
            'sku' => $product->sku,
            'name' => 'Nama Baru',
            'category_id' => $product->category_id,
            'unit_id' => $unit->id,
            'cost_price' => $product->cost_price,
            'selling_price' => 99000,
            'reorder_point' => $product->reorder_point,
            'is_active' => false,
        ])->assertRedirect(route('products.index'));

        $product = $product->fresh();

        $this->assertSame('Nama Baru', $product->name);
        $this->assertSame('99000.00', $product->selling_price);
        $this->assertFalse($product->is_active);
    }

    public function test_destroy_soft_deletes_product(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted($product);
    }

    public function test_staff_finance_cannot_manage_products(): void
    {
        $this->actingAsRole('staff_finance');

        $this->get(route('products.index'))->assertForbidden();
        $this->post(route('products.store'), ['sku' => 'X', 'name' => 'X'])->assertForbidden();
    }
}
