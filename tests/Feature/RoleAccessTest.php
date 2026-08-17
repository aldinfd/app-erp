<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_visiting_back_office(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_users_without_a_role_cannot_access_back_office(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(route('dashboard'))->assertForbidden();
    }

    #[DataProvider('internalRolesProvider')]
    public function test_users_with_any_internal_role_can_access_back_office(string $role): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('auth.roles', [$role]),
            );
    }

    public static function internalRolesProvider(): array
    {
        return [
            'admin' => ['admin'],
            'staff_gudang' => ['staff_gudang'],
            'staff_finance' => ['staff_finance'],
        ];
    }
}
