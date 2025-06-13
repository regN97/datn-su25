<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Inertia\Inertia;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\User;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with([
            'supplier:id,name', // Tải thông tin nhà cung cấp, chỉ lấy id và name
            'status:id,name,code', // Tải thông tin trạng thái PO (PO Status), chỉ lấy id, name và code
            'creator:id,name',    // Tải thông tin người tạo, chỉ lấy id và name
            'approver:id,name',   // Tải thông tin người duyệt (nếu có), chỉ lấy id và name
            'items' => function ($query) {
                // Tải các mặt hàng của đơn hàng
                $query->with('product', function ($productQuery) {
                    // Tải thông tin sản phẩm cho mỗi mặt hàng, bao gồm cả đơn vị (unit)
                    $productQuery->select('id', 'name', 'sku', 'unit_id') // Chọn các trường cần thiết từ bảng products
                                 ->with('unit:id,name'); // Tải thông tin đơn vị và chỉ lấy id, name
                });
            }
        ])->get(); // Lấy tất cả các đơn hàng sau khi đã tải các mối quan hệ

        return Inertia::render('admin/purchase_orders/Index')->with([
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function create(Request $request)
    {
        $query = Product::query();

        // Get suppliers with search if provided
        $suppliersQuery = Supplier::query();
        if ($request->filled('supplier_search')) {
            $suppliersQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier_search . '%')
                    ->orWhere('email', 'like', '%' . $request->supplier_search . '%')
                    ->orWhere('phone', 'like', '%' . $request->supplier_search . '%');
            });
        }
        $suppliers = $suppliersQuery->get();

        // Existing product search logic
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $query->with('suppliers');

        $products = ['data' => $query->get()];

        $user = User::all();

        return Inertia::render('admin/purchase_orders/Create', [
            'products' => $products,
            'suppliers' => $suppliers,
            'users' => $user
        ]);
    }

    public function store(Request $request) {
        dd($request->all());
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

        return Inertia::render('admin/purchase_orders/Trashed', [
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
