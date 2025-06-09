<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Inertia\Inertia;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;

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

    public function show() {}
    public function destroy(string $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->delete();
        PurchaseOrderItem::where('purchase_order_id', $id)->delete();
        return redirect()->back()->with('success', 'Đơn đặt hàng và các sản phẩm liên quan đã được xóa mềm!');
    }

    /**
     * Display a listing of trashed resources.
     */ public function trashed()
    {
        $purchaseOrders = PurchaseOrder::onlyTrashed()
            ->with(['supplier', 'status'])
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'supplier' => $order->supplier, // Truyền cả object
                    'order_date' => $order->order_date,
                    'expected_delivery_date' => $order->expected_delivery_date,
                    'payment_status' => $order->payment_status,
                    'received_status' => $order->received_status,
                    'status' => $order->status, // Truyền cả object
                    'total_amount' => $order->total_amount,
                    'deleted_at' => $order->deleted_at,
                ];
            });

        return Inertia::render('admin/purchase-orders/Trashed', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }
    public function restore(string $id)
    {
        $purchaseOrder = PurchaseOrder::onlyTrashed()->findOrFail($id);
        $purchaseOrder->restore();

        return redirect()->back()->with('success', 'Nhà cung cấp đã được khôi phục!');
    }

    /**
     * Permanently remove the specified resource from storage.
     */
    public function forceDelete(string $id)
    {
        $purchaseOrder = PurchaseOrder::onlyTrashed()->findOrFail($id);
        $purchaseOrder->forceDelete();

        return redirect()->back()->with('success', 'Nhà cung cấp đã được xóa vĩnh viễn!');
    }
}
