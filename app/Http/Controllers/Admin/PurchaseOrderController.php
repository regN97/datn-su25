<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
   public function index()
    {
        $purchaseOrders = PurchaseOrder::with([
            'supplier', // Tải thông tin nhà cung cấp
            'status',   // Tải thông tin trạng thái PO (PO Status)
            'creator',  // Tải thông tin người tạo
            'approver'  // Tải thông tin người duyệt (nếu có)
        ])->get(); // Lấy tất cả các đơn hàng sau khi đã tải các mối quan hệ

        return Inertia::render('admin/purchase-orders/Index')->with([
            'purchaseOrders' => $purchaseOrders,
        ]);
    }
}
