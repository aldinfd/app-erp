<?php

namespace Tests\Feature\Master;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_index_renders_categories_page(): void
    {
        $this->actingAsRole('admin');

        Category::factory()->count(3)->create();

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('master/categories/index')
                    ->has('categories.data', 3),
            );
    }

    public function test_index_searches_by_name(): void
    {
        $this->actingAsRole('admin');

        Category::factory()->create(['name' => 'Pakaian']);
        Category::factory()->create(['name' => 'Elektronik']);

        $this->get(route('categories.index', ['q' => 'paka']))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('categories.total', 1)
                    ->where('categories.data.0.name', 'Pakaian'),
            );
    }

    public function test_store_creates_category(): void
    {
        $this->actingAsRole('staff_gudang');

        $parent = Category::factory()->create(['name' => 'Elektronik']);

        $response = $this->post(route('categories.store'), [
            'name' => 'Mouse & Keyboard',
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect(route('categories.index'));

        $category = Category::query()->where('name', 'Mouse & Keyboard')->first();
        $this->assertNotNull($category);
        $this->assertTrue($category->parent_id === $parent->id);
    }

    public function test_store_requires_a_name(): void
    {
        $this->actingAsRole('admin');

        $this->post(route('categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_update_changes_category(): void
    {
        $this->actingAsRole('admin');

        $category = Category::factory()->create(['name' => 'Pakaian']);

        $this->patch(route('categories.update', $category), ['name' => 'Apparel'])
            ->assertRedirect(route('categories.index'));

        $this->assertSame('Apparel', $category->fresh()->name);
    }

    public function test_update_rejects_category_as_its_own_parent(): void
    {
        $this->actingAsRole('admin');

        $category = Category::factory()->create();

        $this->patch(route('categories.update', $category), [
            'name' => $category->name,
            'parent_id' => $category->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_destroy_soft_deletes_category(): void
    {
        $this->actingAsRole('admin');

        $category = Category::factory()->create();

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertSoftDeleted($category);
    }

    public function test_destroy_is_blocked_when_category_has_children_or_products(): void
    {
        $this->actingAsRole('admin');

        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->delete(route('categories.destroy', $parent))
            ->assertRedirect(route('categories.index'));

        $this->assertNotNull($parent->fresh());

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertNotNull($category->fresh());
    }

    public function test_staff_finance_cannot_manage_categories(): void
    {
        $this->actingAsRole('staff_finance');

        $this->get(route('categories.index'))->assertForbidden();
        $this->post(route('categories.store'), ['name' => 'Baru'])->assertForbidden();
    }
}
