<?php

namespace Tests\Feature\Master;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_index_renders_customers_page(): void
    {
        $this->actingAsRole('admin');

        Customer::factory()->count(3)->create();

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('master/customers/index')
                    ->has('customers.data', 3),
            );
    }

    public function test_index_searches_by_name_email_and_phone(): void
    {
        $this->actingAsRole('admin');

        Customer::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '081234567890']);
        Customer::factory()->create(['name' => 'Ani Lestari']);

        $this->get(route('customers.index', ['q' => 'budi']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('customers.total', 1));

        $this->get(route('customers.index', ['q' => 'budi@example.com']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('customers.total', 1));

        $this->get(route('customers.index', ['q' => '0812']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('customers.total', 1));
    }

    public function test_store_creates_customer(): void
    {
        $this->actingAsRole('staff_finance');

        $this->post(route('customers.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['email' => 'budi@example.com']);
    }

    public function test_store_rejects_invalid_email(): void
    {
        $this->actingAsRole('admin');

        $this->post(route('customers.store'), ['name' => 'Budi', 'email' => 'bukan-email'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_update_changes_customer(): void
    {
        $this->actingAsRole('admin');

        $customer = Customer::factory()->create(['name' => 'Budi']);

        $this->patch(route('customers.update', $customer), [
            'name' => 'Budi Santoso',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
        ])->assertRedirect(route('customers.index'));

        $this->assertSame('Budi Santoso', $customer->fresh()->name);
    }

    public function test_destroy_soft_deletes_customer(): void
    {
        $this->actingAsRole('admin');

        $customer = Customer::factory()->create();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted($customer);
    }

    public function test_staff_gudang_cannot_manage_customers(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('customers.index'))->assertForbidden();
        $this->post(route('customers.store'), ['name' => 'X'])->assertForbidden();
    }
}
