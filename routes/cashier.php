<?php

use Inertia\Inertia;
use App\Http\Controllers\Cashier\VNPayController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cashier\POSController;
use App\Http\Controllers\Cashier\BillLookupController;
use App\Http\Controllers\Cashier\ShiftReportController;
use App\Http\Controllers\Cashier\NotificationController;
use App\Http\Controllers\Cashier\CashierDashboardController;
use App\Http\Controllers\Cashier\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Cashier\CustomerLookupController;

Route::prefix('cashier')->name('cashier.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware(['auth', 'cashier'])->group(function () {
        Route::get('dashboard', [CashierDashboardController::class, 'index'])->name('dashboard');
        Route::post('/add-to-cart', [CashierDashboardController::class, 'addToCart'])->name('cashier.addToCart');
        Route::post('/request-stock', [CashierDashboardController::class, 'requestStock'])->name('requestStock');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('pos/customers', [POSController::class, 'createCustomer'])->name('pos.customers.store');
        Route::get('pos/customers', [POSController::class, 'listCustomers'])->name('pos.customers.index');
        Route::get('pos', [POSController::class, 'index'])->name('pos.index');
        Route::post('/pos/sale', [POSController::class, 'submitSale'])->name('pos.sale');
        Route::post('/pos/session/start', [POSController::class, 'startSession'])->name('session.start');
        Route::post('/pos/session/close', [POSController::class, 'closeSession'])->name('session.close');
        Route::get('/pos/shift-report', [POSController::class, 'shiftReport'])->name('pos.shift.report');
        Route::post('/pos/shift-report/generate', [POSController::class, 'generateShiftReport'])->name('shift.report.generate');
        Route::get('/pos/check-batch/{productId}', [POSController::class, 'checkBatch'])->name('batch.check');
        Route::get('/pos/work-shifts', [POSController::class, 'getWorkShifts'])->name('work_shifts.get');
        Route::get('/pos/sync-inventory', [POSController::class, 'syncInventory'])->name('sync-inventory');
        Route::get('/pos/products', [POSController::class, 'getProductsPublic'])->name('pos.products');
        Route::get('/pos/product/barcode/{barcode}', [POSController::class, 'getProductByBarcode'])->name('pos.product.barcode');


        Route::get('/bill-lookup', [BillLookupController::class, 'index'])->name('bill.lookup');
        Route::post('/bill-lookup/search', [BillLookupController::class, 'search'])->name('bill.lookup.search');
        Route::post('/bill-lookup/{bill}/upload-proof', [BillLookupController::class, 'uploadPaymentProof'])->name('bill.lookup.proof');


        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');

        // Route cho báo cáo ca
        Route::get('/shift-report', [ShiftReportController::class, 'showReport'])->name('shift.report');
        Route::post('/shift-report/save-notes', [ShiftReportController::class, 'saveNotes'])->name('shift.notes.save');
        Route::post('/shift-report/end', [ShiftReportController::class, 'endShift'])->name('shift.end');
        Route::get('/shift-history', [ShiftReportController::class, 'history'])->name('shift.history');

        // VNPay Routes (dành cho POS)
        Route::prefix('pos/vnpay')->name('pos.vnpay.')->group(function () {
            // Tạo link thanh toán (Cashier gọi API)
            Route::post('/create', [VNPayController::class, 'createVNPayPayment'])
                ->name('create');

            // Return URL: VNPay redirect user về sau khi thanh toán
            Route::get('/callback', [VNPayController::class, 'handleVNPayCallback'])
                ->name('callback');

            // IPN (Instant Payment Notification) server-to-server
            Route::post('/ipn', [VNPayController::class, 'handleVNPayIPN'])
                ->name('ipn');
        });
        // Customer Lookup
        Route::prefix('customer')->name('customer.')->group(function () {
            Route::get('/', [CustomerLookupController::class, 'index'])->name('lookup');
            Route::post('/search', [CustomerLookupController::class, 'search'])->name('lookup.search');
            Route::get('/{customer}', [CustomerLookupController::class, 'show'])->name('show');
        });
    });
});
