<?php

namespace Tests\Feature\Master;

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ChartOfAccountTest extends TestCase
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

    public function test_index_renders_chart_of_accounts_page(): void
    {
        $this->actingAsRole('admin');

        ChartOfAccount::factory()->count(3)->create();

        $this->get(route('chart-of-accounts.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('master/chart-of-accounts/index')
                    ->has('accounts.data', 3),
            );
    }

    public function test_index_searches_and_filters_by_type(): void
    {
        $this->actingAsRole('admin');

        ChartOfAccount::factory()->create(['code' => '1-1000', 'name' => 'Kas & Bank', 'type' => 'asset']);
        ChartOfAccount::factory()->create(['code' => '4-1000', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue']);

        $this->get(route('chart-of-accounts.index', ['q' => 'kas']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('accounts.total', 1));

        $this->get(route('chart-of-accounts.index', ['type' => 'revenue']))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('accounts.total', 1)
                    ->where('accounts.data.0.type', 'revenue'),
            );
    }

    public function test_store_creates_account(): void
    {
        $this->actingAsRole('staff_finance');

        $parent = ChartOfAccount::factory()->create(['code' => '1-0000', 'type' => 'asset']);

        $this->post(route('chart-of-accounts.store'), [
            'code' => '1-1100',
            'name' => 'Kas Kecil',
            'type' => 'asset',
            'parent_id' => $parent->id,
            'is_postable' => true,
            'is_active' => true,
        ])->assertRedirect(route('chart-of-accounts.index'));

        $account = ChartOfAccount::query()->where('code', '1-1100')->first();

        $this->assertNotNull($account);
        $this->assertTrue($account->parent_id === $parent->id);
        $this->assertTrue($account->is_postable);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $this->actingAsRole('admin');

        $this->post(route('chart-of-accounts.store'), [
            'code' => '9-9999',
            'name' => 'Akun Aneh',
            'type' => 'bukan-tipe',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('chart_of_accounts', 0);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        $this->actingAsRole('admin');

        ChartOfAccount::factory()->create(['code' => '1-1000']);

        $this->post(route('chart-of-accounts.store'), [
            'code' => '1-1000',
            'name' => 'Duplikat',
            'type' => 'asset',
        ])->assertSessionHasErrors('code');

        $this->assertSame(1, ChartOfAccount::count());
    }

    public function test_update_changes_account(): void
    {
        $this->actingAsRole('admin');

        $account = ChartOfAccount::factory()->create(['name' => 'Kas Besar']);

        $this->patch(route('chart-of-accounts.update', $account), [
            'code' => $account->code,
            'name' => 'Kas & Bank',
            'type' => $account->type,
            'parent_id' => null,
            'is_postable' => true,
            'is_active' => true,
        ])->assertRedirect(route('chart-of-accounts.index'));

        $this->assertSame('Kas & Bank', $account->fresh()->name);
    }

    public function test_destroy_soft_deletes_account_without_children(): void
    {
        $this->actingAsRole('admin');

        $account = ChartOfAccount::factory()->create();

        $this->delete(route('chart-of-accounts.destroy', $account))
            ->assertRedirect(route('chart-of-accounts.index'));

        $this->assertSoftDeleted($account);
    }

    public function test_destroy_is_blocked_when_account_has_children(): void
    {
        $this->actingAsRole('admin');

        $parent = ChartOfAccount::factory()->create();
        ChartOfAccount::factory()->create(['parent_id' => $parent->id]);

        $this->delete(route('chart-of-accounts.destroy', $parent))
            ->assertRedirect(route('chart-of-accounts.index'));

        $this->assertNotNull($parent->fresh());
    }

    public function test_staff_gudang_cannot_manage_chart_of_accounts(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('chart-of-accounts.index'))->assertForbidden();
        $this->post(route('chart-of-accounts.store'), [
            'code' => 'X',
            'name' => 'X',
            'type' => 'asset',
        ])->assertForbidden();
    }
}
