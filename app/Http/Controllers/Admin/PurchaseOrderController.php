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
    public function show($id)
    {
        // Lấy PurchaseOrder theo ID và load các mối quan hệ cần thiết
        $purchaseOrder = PurchaseOrder::with([
            'supplier:id,name',
            'status:id,name',
            'creator:id,name',
            'approver:id,name',
            'items' => function ($query) {
                // Load sản phẩm chi tiết cho mỗi purchase order item
                $query->with('product:id,name,sku');
            }
        ])->findOrFail($id); // Tìm đơn hàng theo ID hoặc báo lỗi 404 nếu không tìm thấy

        // Truyền trực tiếp đối tượng purchaseOrder đã load cho Inertia
        return Inertia::render('admin/purchase-orders/Show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }
}
