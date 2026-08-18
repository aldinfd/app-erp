<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StockOpnameControllerTest extends TestCase
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

    public function test_index_renders_opname_page_with_current_stock(): void
    {
        $this->actingAsRole('staff_gudang');

        $product = Product::factory()->create();

        $this->get(route('stock-opname.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('inventory/stock-opname/index')
                    ->has('products.data', 1)
                    ->where('products.data.0.stock_qty', '0.00'),
            );
    }

    public function test_adjust_updates_stock_and_records_movement(): void
    {
        Event::fake(); // cegah listener notifikasi membutuhkan data role
        $user = $this->actingAsRole('admin');

        $product = Product::factory()->create(['stock_qty' => 12]);

        $this->post(route('stock-opname.adjust'), [
            'product_id' => $product->id,
            'new_qty' => 9,
            'note' => 'Opname: 3 pcs rusak',
        ])->assertRedirect(route('stock-opname.index'));

        $product = $product->fresh();
        $movement = StockMovement::query()->first();

        $this->assertSame(9.0, (float) $product->stock_qty);
        $this->assertSame('adjust', $movement->type);
        $this->assertSame(-3.0, (float) $movement->qty);
        $this->assertSame('stock_opname', $movement->reference_type);
        $this->assertSame($user->id, $movement->user_id);
        $this->assertSame('Opname: 3 pcs rusak', $movement->note);
    }

    public function test_adjust_rejects_fractional_qty_for_non_fraction_unit(): void
    {
        $this->actingAsRole('admin');

        $product = Product::factory()->create(['stock_qty' => 10]); // satuan factory default: tidak pecahan

        $this->from(route('stock-opname.index'))
            ->post(route('stock-opname.adjust'), [
                'product_id' => $product->id,
                'new_qty' => 8.5,
                'note' => 'Opname desimal',
            ])->assertSessionHasErrors('new_qty');

        $this->assertSame(0, StockMovement::count());
        $this->assertSame(10.0, (float) $product->fresh()->stock_qty);
    }

    public function test_adjust_allows_fractional_qty_for_kilogram(): void
    {
        Event::fake();
        $this->actingAsRole('staff_gudang');

        $kilogram = Unit::factory()->create(['allows_fraction' => true]);
        $product = Product::factory()->create(['stock_qty' => 10, 'unit_id' => $kilogram->id]);

        $this->post(route('stock-opname.adjust'), [
            'product_id' => $product->id,
            'new_qty' => 8.75,
            'note' => 'Opname timbangan',
        ])->assertRedirect(route('stock-opname.index'));

        $product = $product->fresh();
        $movement = StockMovement::query()->first();

        $this->assertSame(8.75, (float) $product->stock_qty);
        $this->assertSame(-1.25, (float) $movement->qty);
    }

    public function test_adjust_with_same_qty_flash_error_without_movement(): void
    {
        $this->actingAsRole('admin');

        $product = Product::factory()->create(['stock_qty' => 7]);

        $this->from(route('stock-opname.index'))
            ->post(route('stock-opname.adjust'), [
                'product_id' => $product->id,
                'new_qty' => 7,
                'note' => 'Tidak ada selisih',
            ])->assertRedirect(route('stock-opname.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, StockMovement::count());
    }

    public function test_adjust_requires_note_and_non_negative_qty(): void
    {
        $this->actingAsRole('admin');

        $product = Product::factory()->create(['stock_qty' => 5]);

        $this->from(route('stock-opname.index'))
            ->post(route('stock-opname.adjust'), [
                'product_id' => $product->id,
                'new_qty' => 3,
                'note' => '',
            ])->assertSessionHasErrors('note');

        $this->from(route('stock-opname.index'))
            ->post(route('stock-opname.adjust'), [
                'product_id' => $product->id,
                'new_qty' => -1,
                'note' => 'Negatif',
            ])->assertSessionHasErrors('new_qty');

        $this->assertSame(5.0, (float) $product->fresh()->stock_qty);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_adjust_rejects_unknown_product(): void
    {
        $this->actingAsRole('admin');

        $this->from(route('stock-opname.index'))
            ->post(route('stock-opname.adjust'), [
                'product_id' => 999999,
                'new_qty' => 3,
                'note' => 'Produk tidak ada',
            ])->assertSessionHasErrors('product_id');
    }

    public function test_staff_finance_cannot_do_opname(): void
    {
        $this->actingAsRole('staff_finance');

        $this->get(route('stock-opname.index'))->assertForbidden();

        $this->post(route('stock-opname.adjust'), [
            'product_id' => 1,
            'new_qty' => 3,
            'note' => 'X',
        ])->assertForbidden();
    }
}
