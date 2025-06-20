<?php

use Inertia\Inertia;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductBatchController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\ProductSupplierController;
use App\Http\Controllers\Admin\PurchaseReceiptController;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    // Router Categories
    Route::get('categories/trashed', [CategoryController::class, 'trashed'])->name('categories.trashed');
    Route::post('categories/{cat}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
    Route::delete('categories/{cat}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.forceDelete');
    Route::resource('categories', CategoryController::class);

    // Router Products
    Route::resource('products', ProductController::class);

    Route::resource('units',UnitController::class);

    // Router Suppliers   
    Route::get('suppliers/trashed', [SupplierController::class, 'trashed'])->name('suppliers.trashed');
    Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
    Route::delete('suppliers/{supplier}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.forceDelete');
    Route::resource('suppliers', SupplierController::class);

    Route::resource('purchaseReturn', PurchaseReturnController::class);

    Route::resource('batches', BatchController::class);

    Route::resource('product-batches', ProductBatchController::class);

    Route::get('purchase-orders/trashed', [PurchaseOrderController::class, 'trashed'])->name('purchase-orders.trashed');
    Route::post('purchase-orders/{supplier}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
    Route::delete('purchase-orders/{supplier}/force-delete', [PurchaseOrderController::class, 'forceDelete'])->name('purchase-orders.forceDelete');
    Route::post('purchase-orders/{po_id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{po_id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');

    Route::resource('purchase-orders', PurchaseOrderController::class);

    Route::post('inventory/{id}', [InventoryController::class, 'updateI'])->name('inventory.update');
    Route::resource('inventory', InventoryController::class);
    Route::resource('users', UserController::class);


})->middleware(['auth', 'verified']);


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

