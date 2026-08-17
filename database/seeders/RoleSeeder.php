<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Role ERP: admin, staff_gudang, staff_finance (lihat plan.md Phase 1).
     *
     * @var array<int, string>
     */
    public const ROLES = ['admin', 'staff_gudang', 'staff_finance'];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        // Bersihkan cache permission setelah seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
