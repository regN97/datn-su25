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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isEmpty;

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

    public function store(Request $request)
    {

        $user_id = $request->user_id;
        if ($user_id === null) {
            $user_id = Auth::id();
        }

        // 2. Xác định po_number (order_code)
        $po_number = $request->order_code;
        if ($po_number === null) {
            $today = Carbon::now()->format('Ymd');
            $prefix = "PO-{$today}-";

            // Lấy po_number cuối cùng trong ngày hiện tại
            $lastPo = PurchaseOrder::where('po_number', 'like', "{$prefix}%")
                ->orderByDesc('po_number')
                ->first();

            if ($lastPo) {
                // Tách số thứ tự cuối cùng
                $lastNumber = (int)substr($lastPo->po_number, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }

            $po_number = "{$prefix}{$nextNumber}";
        }

        // 3. Khởi tạo dữ liệu để insert vào bảng purchase_orders
        $po_data = [
            'po_number' => $po_number,
            'supplier_id' => $request->supplier_id,
            'status_id' => 1,
            'order_date' => now(),
            'expected_delivery_date' => $request->expected_import_date,
            'actual_delivery_date' => null,
            'discount_type' => $request->discount['type'] ?? null,
            'discount_amount' => $request->discount['value'] ?? null,
            'total_amount' => $request->total_amount,
            'created_by' => $user_id,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $request->note,
        ];

        $purchaseOrder = PurchaseOrder::create($po_data);
        $purchaseOrderId = $purchaseOrder->id;

        $po_items_data = [];
        foreach ($request->products as $product) {
            $po_items_data[] = [
                'purchase_order_id' => $purchaseOrderId,
                'product_id'        => $product['id'],
                'product_name'      => $product['name'],
                'product_sku'       => $product['sku'],
                'ordered_quantity'  => $product['quantity'],
                'received_quantity' => 0,
                'quantity_returned' => 0,
                'unit_cost'         => $product['purchase_price'],
                'subtotal'          => $product['sub_total'],
                'discount_amount'   => 0,
                'discount_type'     => 'amount',
                'notes'             => null,
            ];
        }

        // Insert nhiều bản ghi vào bảng purchase_order_items
        PurchaseOrderItem::insert($po_items_data);

        return Inertia::render('admin/purchase_orders/Show', []);


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
