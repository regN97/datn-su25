<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceipt;
use Inertia\Inertia;

class PurchaseReceiptController extends Controller
{

    public function index()
    {
        $purchase_receipts = PurchaseReceipt::select(
            'purchase_receipts.*',
            'purchase_orders.po_number'
        )
         ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
         ->get();
        return Inertia::render('admin/purchase_receipts/Index')->with(['purchase_receipts' => $purchase_receipts]);

    }





}





?>