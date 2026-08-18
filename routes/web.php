<?php

use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ChartOfAccountController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Master\VendorController;
use Illuminate\Support\Facades\Route;

/*
|----------------------------------------------------------------------
| Storefront (publik) — customer tanpa login ERP
|----------------------------------------------------------------------
*/
Route::inertia('/', 'storefront/home')->name('home');

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
| Master data gudang — admin & staff_gudang
|----------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin|staff_gudang'])->group(function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('units', UnitController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
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
});

require __DIR__.'/settings.php';
