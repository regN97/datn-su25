<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cashier\POSController;
use App\Http\Controllers\Cashier\Auth\AuthenticatedSessionController;

Route::prefix('cashier')->name('cashier.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware(['auth', 'cashier'])->group(function () {
        Route::get('dashboard', fn() => Inertia::render('cashier/Dashboard'))->name('dashboard');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('pos/customers', [POSController::class, 'storeCustomer'])->name('pos.customers.store');
    });
    Route::get('pos', [POSController::class, 'index'])->name('pos.index');
});
