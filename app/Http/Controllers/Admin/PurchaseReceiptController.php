<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceipt;
use Inertia\Inertia;

class PurchaseReceiptController extends Controller{

public function index() {
    $purchase_receipts = PurchaseReceipt::all();
    return Inertia::render('admin/purchase_receipts/Index')->with(['purchase_receipts' => $purchase_receipts]);

}





}





?>