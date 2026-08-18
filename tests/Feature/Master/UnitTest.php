<?php

namespace Tests\Feature\Master;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UnitTest extends TestCase
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

    public function test_index_renders_units_page(): void
    {
        $this->actingAsRole('admin');

        Unit::factory()->count(3)->create();

        $this->get(route('units.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('master/units/index')
                    ->has('units.data', 3),
            );
    }

    public function test_index_searches_by_name_and_abbreviation(): void
    {
        $this->actingAsRole('admin');

        Unit::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'kg']);
        Unit::factory()->create(['name' => 'Pieces', 'abbreviation' => 'pcs']);

        $this->get(route('units.index', ['q' => 'kilo']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('units.total', 1));

        $this->get(route('units.index', ['q' => 'pcs']))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('units.total', 1)
                    ->where('units.data.0.abbreviation', 'pcs'),
            );
    }

    public function test_store_creates_unit(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->post(route('units.store'), ['name' => 'Lusin', 'abbreviation' => 'lusin'])
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('units', ['abbreviation' => 'lusin']);
    }

    public function test_store_rejects_duplicate_abbreviation(): void
    {
        $this->actingAsRole('admin');

        Unit::factory()->create(['abbreviation' => 'kg']);

        $this->post(route('units.store'), ['name' => 'Kilogram Baru', 'abbreviation' => 'kg'])
            ->assertSessionHasErrors('abbreviation');

        $this->assertSame(1, Unit::count());
    }

    public function test_update_changes_unit(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create(['name' => 'Piece']);

        $this->patch(route('units.update', $unit), ['name' => 'Pieces', 'abbreviation' => $unit->abbreviation])
            ->assertRedirect(route('units.index'));

        $this->assertSame('Pieces', $unit->fresh()->name);
    }

    public function test_destroy_deletes_unused_unit(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();

        $this->delete(route('units.destroy', $unit))
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_destroy_is_blocked_when_unit_is_used_by_product(): void
    {
        $this->actingAsRole('admin');

        $unit = Unit::factory()->create();
        Product::factory()->create(['unit_id' => $unit->id]);

        $this->delete(route('units.destroy', $unit))
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_staff_finance_cannot_manage_units(): void
    {
        $this->actingAsRole('staff_finance');

        $this->get(route('units.index'))->assertForbidden();
        $this->post(route('units.store'), ['name' => 'X', 'abbreviation' => 'x'])->assertForbidden();
    }
}
