<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SupplierController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function () {

    Route::resource('categories', CategoryController::class);

    Route::resource('products', ProductController::class);

    Route::resource('suppliers', SupplierController::class);
})->middleware(['auth', 'verified']);


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
