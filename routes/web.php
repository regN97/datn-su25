<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductSupplierController;
use App\Http\Controllers\Admin\PurchaseReturnController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('categories/trashed', [CategoryController::class, 'trashed'])->name('categories.trashed'); 
    Route::post('categories/{cat}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
    Route::delete('categories/{cat}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.forceDelete');
    Route::resource('categories', CategoryController::class);

    Route::resource('products', ProductController::class);

    Route::get('suppliers/trashed', [SupplierController::class, 'trashed'])->name('suppliers.trashed');
    Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
    Route::delete('suppliers/{supplier}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.forceDelete');
    Route::resource('suppliers', SupplierController::class);

    Route::resource('purchaseReturn', PurchaseReturnController::class);
    
})->middleware(['auth', 'verified']);


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
