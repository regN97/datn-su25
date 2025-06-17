<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
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
                    $productQuery->select('id', 'unit_id') // Chọn các trường cần thiết từ bảng products
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

        $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', '=', $purchaseOrderId)->with('product')->get();
        $purchaseOrder = PurchaseOrder::where('id', '=', $purchaseOrderId)->with('status')->get();
        $productQuery = Product::query();
        $products = ['data' => $productQuery->get()];
        $supplierQuery = Supplier::query();
        $suppliers = $supplierQuery->get();
        $user = User::all();

        return Inertia::render('admin/purchase_orders/Show', [
            'purchaseOrderItem' => $purchaseOrderItem,
            'purchaseOrder' => $purchaseOrder,
            'products' => $products,
            'suppliers' => $suppliers,
            'users' => $user,

        ]);
    }

    public function show($id) {
        // dd($id);
        $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', '=', $id)->with('product')->get();
        $purchaseOrder = PurchaseOrder::where('id', '=', $id)->with('status')->get();
        $productQuery = Product::query();
        $products = ['data' => $productQuery->get()];
        $supplierQuery = Supplier::query();
        $suppliers = $supplierQuery->get();
        $user = User::all();

        return Inertia::render('admin/purchase_orders/Show', [
            'purchaseOrderItem' => $purchaseOrderItem,
            'purchaseOrder' => $purchaseOrder,
            'products' => $products,
            'suppliers' => $suppliers,
            'users' => $user,

        ]);
    }

    public function destroy(string $id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);

        // Soft delete các item
        $order->items->each(function ($item) {
            $item->delete();
        });

        $order->delete();

        return redirect()->back()->with('success', 'Đơn đặt hàng và các sản phẩm liên quan đã được xóa mềm!');
    }


    /**
     * Display a listing of trashed resources.
     */
    public function trashed()
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

        // Lấy tất cả các item_id của đơn hàng
        $itemIds = PurchaseOrderItem::withTrashed()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->pluck('id')
            ->toArray();

        // 1. Xoá batch_items liên quan
        if (!empty($itemIds)) {
            BatchItem::withTrashed()
                ->whereIn('purchase_order_item_id', $itemIds)
                ->forceDelete();

            // 2. Xoá purchase_return_items liên quan
            PurchaseReturnItem::withTrashed()
                ->whereIn('purchase_order_item_id', $itemIds)
                ->forceDelete();
        }

        // 3. Xoá purchase_returns
        PurchaseReturn::withTrashed()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->forceDelete();

        // 4. Xoá purchase_order_items
        PurchaseOrderItem::withTrashed()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->forceDelete();

        // 5. Xoá batches
        Batch::withTrashed()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->forceDelete();

        // 6. Cuối cùng xoá đơn đặt hàng
        $purchaseOrder->forceDelete();

        return redirect()->back()->with('success', 'Đơn đặt hàng và toàn bộ dữ liệu liên quan đã được xoá vĩnh viễn!');
    }


}
