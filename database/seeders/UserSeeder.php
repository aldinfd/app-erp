<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Password awal semua user seed (dev saja — ganti di production).
     */
    public const DEFAULT_PASSWORD = 'password';

    /**
     * @var array<string, string> role => email user seed
     */
    public const SEED_USERS = [
        'admin' => 'admin@erp.test',
        'staff_gudang' => 'staff.gudang@erp.test',
        'staff_finance' => 'staff.finance@erp.test',
    ];

    public function run(): void
    {
        foreach (self::SEED_USERS as $role => $email) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => ucfirst(str_replace('_', ' ', $role)),
                    'email_verified_at' => now(),
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                ],
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
