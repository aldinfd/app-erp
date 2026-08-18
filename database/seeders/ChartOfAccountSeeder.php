<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CoA standar — akun header (is_postable = 0) + akun anak postable.
 * Akun di sini jadi dasar mapping journal_mappings (Phase 6).
 */
class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $headers = [
            ['code' => '1-0000', 'name' => 'Aset', 'type' => 'asset'],
            ['code' => '2-0000', 'name' => 'Liabilitas', 'type' => 'liability'],
            ['code' => '3-0000', 'name' => 'Ekuitas', 'type' => 'equity'],
            ['code' => '4-0000', 'name' => 'Pendapatan', 'type' => 'revenue'],
            ['code' => '5-0000', 'name' => 'Beban', 'type' => 'expense'],
        ];

        foreach ($headers as $header) {
            ChartOfAccount::firstOrCreate(
                ['code' => $header['code']],
                [...$header, 'is_postable' => false],
            );
        }

        $accounts = [
            ['code' => '1-1000', 'name' => 'Kas & Bank', 'type' => 'asset', 'parent' => '1-0000'],
            ['code' => '1-2000', 'name' => 'Piutang Usaha', 'type' => 'asset', 'parent' => '1-0000'],
            ['code' => '1-3000', 'name' => 'Persediaan', 'type' => 'asset', 'parent' => '1-0000'],
            ['code' => '2-1000', 'name' => 'Hutang Vendor', 'type' => 'liability', 'parent' => '2-0000'],
            ['code' => '2-2000', 'name' => 'Utang PPN', 'type' => 'liability', 'parent' => '2-0000'],
            ['code' => '3-1000', 'name' => 'Modal Pemilik', 'type' => 'equity', 'parent' => '3-0000'],
            ['code' => '3-2000', 'name' => 'Laba Ditahan', 'type' => 'equity', 'parent' => '3-0000'],
            ['code' => '4-1000', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue', 'parent' => '4-0000'],
            ['code' => '5-1000', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'parent' => '5-0000'],
            ['code' => '5-2000', 'name' => 'Beban Operasional', 'type' => 'expense', 'parent' => '5-0000'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'parent_id' => ChartOfAccount::where('code', $account['parent'])->value('id'),
                ],
            );
        }

        $this->command?->info(sprintf(
            'Chart of accounts: %d akun.',
            DB::table('chart_of_accounts')->count(),
        ));
    }
}
