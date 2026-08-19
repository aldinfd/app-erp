<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\FinancialReportController;
use App\Http\Controllers\Finance\GeneralLedgerController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ChartOfAccountController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Master\VendorController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\VendorInvoiceController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Webhook\MidtransController;
use Illuminate\Support\Facades\Route;

/*
|----------------------------------------------------------------------
| Storefront (publik) — customer tanpa login ERP
|----------------------------------------------------------------------
*/
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::inertia('cart', 'storefront/cart')->name('cart');
Route::get('checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('payment/finish', [CheckoutController::class, 'finish'])->name('payment.finish');

/*
|----------------------------------------------------------------------
| Webhook Midtrans — tanpa CSRF (dikecualikan di bootstrap/app.php),
| di-throttle + verifikasi signature di PaymentService
|----------------------------------------------------------------------
*/
Route::post('webhooks/midtrans', MidtransController::class)->middleware('throttle:webhooks')->name('webhooks.midtrans');

/*
|----------------------------------------------------------------------
| Back-office (internal) — hanya user ERP dengan role internal
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_gudang|staff_finance'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

/*
|----------------------------------------------------------------------
| Master data & inventory gudang — admin & staff_gudang
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_gudang'])->group(function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('units', UnitController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Riwayat stok hanya baca; opname mengoreksi stok lewat StockService.
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    Route::get('stock-opname', [StockOpnameController::class, 'index'])->name('stock-opname.index');
    Route::post('stock-opname', [StockOpnameController::class, 'adjust'])->name('stock-opname.adjust');
});

/*
|----------------------------------------------------------------------
| Master data keuangan & relasi — admin & staff_finance
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_finance'])->group(function () {
    Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('vendors', VendorController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('chart-of-accounts', ChartOfAccountController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Sales order dari storefront: list/detail baca-saja + batalkan order
    // (pembayaran diproses otomatis via webhook Midtrans).
    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/{sales_order}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::post('sales-orders/{sales_order}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
});

/*
|----------------------------------------------------------------------
| Purchase order gudang — admin & staff_gudang (buat/pesan/terima/batal).
| Didaftarkan sebelum group lihat agar GET create tidak tertangkap
| route show {purchase_order}.
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_gudang'])->group(function () {
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::post('purchase-orders/{purchase_order}/ordered', [PurchaseOrderController::class, 'markOrdered'])->name('purchase-orders.ordered');
    Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
});

/*
|----------------------------------------------------------------------
| Purchase order lihat — semua role internal (finance perlu membuka PO
| untuk mencatat invoice vendor & pembayaran).
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_gudang|staff_finance'])->group(function () {
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
});

/*
|----------------------------------------------------------------------
| Invoice vendor & pembayaran — admin & staff_finance (requirement:
| Staff Finance kelola pembayaran & invoice).
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_finance'])->group(function () {
    Route::post('purchase-orders/{purchase_order}/vendor-invoice', [VendorInvoiceController::class, 'store'])->name('vendor-invoices.store');
    Route::post('vendor-invoices/{vendor_invoice}/payments', [VendorInvoiceController::class, 'storePayment'])->name('vendor-invoices.payments.store');
});

/*
|----------------------------------------------------------------------
| Finance: jurnal umum (+ jurnal manual), buku besar, laporan keuangan —
| admin & staff_finance (plan Phase 6). Didaftarkan create sebelum
| {journal_entry} agar tidak tertangkap param show.
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_finance'])->group(function () {
    Route::get('journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
    Route::get('journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('journal-entries/{journal_entry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');

    Route::get('general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');

    Route::get('reports/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
});

require __DIR__.'/settings.php';
