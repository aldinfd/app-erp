<?php

use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ChartOfAccountController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Master\VendorController;
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
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
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

require __DIR__.'/settings.php';
