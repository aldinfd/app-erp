<?php

namespace Tests\Feature\Master;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class VendorTest extends TestCase
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

    public function test_index_renders_vendors_page(): void
    {
        $this->actingAsRole('admin');

        Vendor::factory()->count(3)->create();

        $this->get(route('vendors.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('master/vendors/index')
                    ->has('vendors.data', 3),
            );
    }

    /**
     * Seeder wajib menyediakan vendor aktif — tanpa itu dropdown
     * form Purchase Order kosong (kejadian 2026-08-18).
     */
    public function test_master_data_seeder_provides_active_vendors(): void
    {
        $this->seed(MasterDataSeeder::class);

        $vendors = Vendor::query()->where('is_active', true)->get();

        $this->assertGreaterThanOrEqual(4, $vendors->count());
        $this->assertSame(0, Vendor::query()->where('is_active', false)->count());
    }

    public function test_index_searches_and_filters_by_status(): void
    {
        $this->actingAsRole('admin');

        Vendor::factory()->create(['name' => 'CV Sumber Makmur', 'email' => 'sales@sumbermakmur.id']);
        Vendor::factory()->inactive()->create(['name' => 'PT Nonaktif']);

        $this->get(route('vendors.index', ['q' => 'makmur']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('vendors.total', 1));

        $this->get(route('vendors.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('vendors.total', 1));

        $this->get(route('vendors.index', ['status' => 'active']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('vendors.total', 1));
    }

    public function test_store_creates_vendor(): void
    {
        $this->actingAsRole('staff_finance');

        $this->post(route('vendors.store'), [
            'name' => 'CV Sumber Makmur',
            'email' => 'sales@sumbermakmur.id',
            'phone' => '0215551234',
            'address' => 'Jl. Industri No. 10',
            'is_active' => true,
        ])->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', ['name' => 'CV Sumber Makmur']);
    }

    public function test_update_changes_vendor(): void
    {
        $this->actingAsRole('admin');

        $vendor = Vendor::factory()->create(['name' => 'CV Lama']);

        $this->patch(route('vendors.update', $vendor), [
            'name' => 'CV Baru',
            'email' => $vendor->email,
            'phone' => $vendor->phone,
            'address' => $vendor->address,
            'is_active' => false,
        ])->assertRedirect(route('vendors.index'));

        $vendor = $vendor->fresh();

        $this->assertSame('CV Baru', $vendor->name);
        $this->assertFalse($vendor->is_active);
    }

    public function test_destroy_soft_deletes_vendor(): void
    {
        $this->actingAsRole('admin');

        $vendor = Vendor::factory()->create();

        $this->delete(route('vendors.destroy', $vendor))
            ->assertRedirect(route('vendors.index'));

        $this->assertSoftDeleted($vendor);
    }

    public function test_staff_gudang_cannot_manage_vendors(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('vendors.index'))->assertForbidden();
        $this->post(route('vendors.store'), ['name' => 'X'])->assertForbidden();
    }
}
