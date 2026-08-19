<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\JournalMapping;
use Illuminate\Database\Seeder;

/**
 * Mapping akun auto-jurnal (schema-database.md §8.3): sales_payment (Phase 4,
 * + HPP/persediaan 2026-08-18) + purchase_received/purchase_payment (Phase 5).
 * Id akun di-resolve by code — tidak ada id yang di-hard-code.
 */
class JournalMappingSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT, 'account_key' => JournalMapping::KEY_KAS_BANK, 'code' => '1-1000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT, 'account_key' => JournalMapping::KEY_PENDAPATAN_PENJUALAN, 'code' => '4-1000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT, 'account_key' => JournalMapping::KEY_UTANG_PPN, 'code' => '2-2000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT, 'account_key' => JournalMapping::KEY_HPP, 'code' => '5-1000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_SALES_PAYMENT, 'account_key' => JournalMapping::KEY_PERSEDIAAN, 'code' => '1-3000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_PURCHASE_RECEIVED, 'account_key' => JournalMapping::KEY_PERSEDIAAN, 'code' => '1-3000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_PURCHASE_RECEIVED, 'account_key' => JournalMapping::KEY_HUTANG_VENDOR, 'code' => '2-1000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_PURCHASE_PAYMENT, 'account_key' => JournalMapping::KEY_HUTANG_VENDOR, 'code' => '2-1000'],
            ['transaction_type' => JournalMapping::TRANSACTION_TYPE_PURCHASE_PAYMENT, 'account_key' => JournalMapping::KEY_KAS_BANK, 'code' => '1-1000'],
        ];

        foreach ($mappings as $mapping) {
            JournalMapping::firstOrCreate(
                ['transaction_type' => $mapping['transaction_type'], 'account_key' => $mapping['account_key']],
                ['account_id' => ChartOfAccount::where('code', $mapping['code'])->value('id')],
            );
        }

        $this->command?->info('Journal mappings: sales_payment + purchase seeded.');
    }
}
