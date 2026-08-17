<?php

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

require __DIR__.'/settings.php';
