<?php

use Inertia\Inertia;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    DashboardController,
    CategoryController,
    ProductController,
    UnitController,
    SupplierController,
    PurchaseReturnController,
    BatchController,
    CustomerController,
    ProductBatchController,
    PurchaseOrderController,
    InventoryController,
    UserController
};

Route::get('/', fn() => Inertia::render('Welcome'))->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin']) // Chỉ admin mới được vào
    ->name('dashboard');
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    // Categories
    Route::get('categories/trashed', [CategoryController::class, 'trashed'])->name('categories.trashed');
    Route::post('categories/{cat}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
    Route::delete('categories/{cat}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.forceDelete');
    Route::resource('categories', CategoryController::class);

    // Products, Units
    Route::resource('products', ProductController::class);
    Route::resource('units', UnitController::class);

    // Suppliers
    Route::get('suppliers/trashed', [SupplierController::class, 'trashed'])->name('suppliers.trashed');
    Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
    Route::delete('suppliers/{supplier}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.forceDelete');
    Route::resource('suppliers', SupplierController::class);

    // Purchase
    Route::resource('purchaseReturn', PurchaseReturnController::class);


    Route::get('purchase-orders/trashed', [PurchaseOrderController::class, 'trashed'])->name('purchase-orders.trashed');
    Route::post('purchase-orders/{supplier}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
    Route::delete('purchase-orders/{supplier}/force-delete', [PurchaseOrderController::class, 'forceDelete'])->name('purchase-orders.forceDelete');
    Route::post('purchase-orders/{po_id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('/purchase-orders/{id}/status', [PurchaseOrderController::class, 'getStatus'])->name('purchase-orders.status');
    Route::get('/purchase-orders/{id}/imported-quantities', [PurchaseOrderController::class, 'getImportedQuantities'])->name('purchase-orders.imported-quantities');
    Route::post('purchase-orders/{po_id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::resource('purchase-orders', PurchaseOrderController::class);

    // Batches
    Route::post('batches/{id}/pay', [BatchController::class, 'pay'])->name('batches.pay');
    Route::get('batches/add/{po_id}', [BatchController::class, 'add'])->name('batches.add');
    Route::post('batches/save', [BatchController::class, 'save'])->name('batches.save');
    Route::resource('batches', BatchController::class);

    // Others
    Route::resource('inventory', InventoryController::class);
    Route::resource('users', UserController::class);

    //Customer
    Route::get('customers/trashed', [CustomerController::class, 'trashed'])->name('customers.trashed');
    Route::post('customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
    Route::delete('customers/{customer}/force-delete', [CustomerController::class, 'forceDelete'])->name('customers.forceDelete');
    Route::resource('customers', CustomerController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/cashier.php';
