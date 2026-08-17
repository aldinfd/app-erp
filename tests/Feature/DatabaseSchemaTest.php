<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('membuat semua 18 tabel custom ERP dari migration', function () {
    Artisan::call('migrate', ['--force' => true]);

    $tables = [
        // Master data
        'categories', 'units', 'products', 'customers', 'vendors', 'chart_of_accounts',
        // Sales
        'sales_orders', 'sales_order_items', 'invoices', 'payments',
        // Purchase
        'purchase_orders', 'purchase_order_items', 'vendor_invoices', 'vendor_payments',
        // Inventory
        'stock_movements',
        // Finance
        'journal_entries', 'journal_lines', 'journal_mappings',
    ];

    $missing = array_values(array_filter(
        $tables,
        fn (string $table): bool => ! Schema::hasTable($table),
    ));

    expect($missing)->toBe([]);
});

it('struktur kolom kunci sesuai keputusan desain database.md', function () {
    Artisan::call('migrate', ['--force' => true]);

    // Keputusan #1: users tanpa kolom role (RBAC via spatie)
    expect(Schema::hasColumn('users', 'role'))->toBeFalse();

    // Keputusan #2: stock_movements append-only dengan qty signed delta
    expect(Schema::hasColumn('stock_movements', 'updated_at'))->toBeFalse();
    expect(Schema::hasColumn('stock_movements', 'qty'))->toBeTrue();
    expect(Schema::hasColumn('stock_movements', 'before_qty'))->toBeTrue();
    expect(Schema::hasColumn('stock_movements', 'after_qty'))->toBeTrue();

    // Keputusan #3: tabel journal_mappings
    expect(Schema::hasTable('journal_mappings'))->toBeTrue();

    // Keputusan #4: payments & vendor_payments terpisah
    expect(Schema::hasTable('payments'))->toBeTrue();
    expect(Schema::hasTable('vendor_payments'))->toBeTrue();

    // Keputusan #7: chart_of_accounts punya is_postable
    expect(Schema::hasColumn('chart_of_accounts', 'is_postable'))->toBeTrue();
});
