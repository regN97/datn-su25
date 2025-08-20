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
use Illuminate\Support\Facades\DB;

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
        ])->orderByDesc('created_at')->get(); // Lấy tất cả các đơn hàng sau khi đã tải các mối quan hệ

        return Inertia::render('admin/purchase_orders/Index')->with([
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function create(Request $request)
    {
        $query = Product::where('is_active', true);

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

        $user = User::find(Auth::id())->toArray();

        return Inertia::render('admin/purchase_orders/Create', [
            'products' => $products,
            'suppliers' => $suppliers,'suppliers' => $suppliers,
            'users' => $user
        ]);
    }

public function store(Request $request)
{
    // 1. Validate cơ bản
    $validated = $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'expected_import_date' => 'required|date',
        'products' => 'required|array|min:1',
        'products.*.id' => 'required|exists:products,id',
        'products.*.quantity' => 'required|numeric|min:1',
        'products.*.purchase_price' => 'required|numeric|min:0.01',
    ], [
        'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
        'expected_import_date.required' => 'Vui lòng chọn ngày nhập dự kiến.',
        'products.required' => 'Danh sách sản phẩm là bắt buộc.',
        'products.*.purchase_price.required' => 'Vui lòng nhập đơn giá cho từng sản phẩm.',
        'products.*.purchase_price.min' => 'Đơn giá phải lớn hơn 0.',
    ]);

    $order_date = now();

    // 2. Validate logic ngày nhập dự kiến >= ngày đặt hàng
    if (Carbon::parse($request->expected_import_date)->lt($order_date->startOfDay())) {
        return redirect()->back()->with('error', 'Ngày nhập dự kiến phải lớn hơn hoặc bằng ngày đặt hàng.');
    }

    DB::beginTransaction();
    try {
        // 3. Sinh po_number
        $today = $order_date->format('Ymd');
        $prefix = "PO-{$today}-";
        $lastPo = PurchaseOrder::withTrashed()
            ->where('po_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING_INDEX(po_number, "-", -1) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = $lastPo ? str_pad(((int) substr($lastPo->po_number, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $po_number = $request->order_code ?? "{$prefix}{$nextNumber}";

        // 4. Tạo purchase order
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => $po_number,
            'supplier_id' => $request->supplier_id,
            'status_id' => 1,
            'order_date' => $order_date,
            'expected_delivery_date' => $request->expected_import_date,
            'discount_type' => $request->discount['type'] ?? null,
            'discount_amount' => $request->discount['value'] ?? null,
            'total_amount' => $request->total_amount,
            'created_by' => $request->user_id ?? Auth::id(),
            'notes' => $request->note,
        ]);

        // 5. Thêm items
        $po_items_data = [];
        foreach ($request->products as $product) {
            if (!isset($product['id'], $product['quantity'], $product['purchase_price'])) {
                throw new \Exception("Thiếu thông tin sản phẩm: ID, số lượng hoặc đơn giá.");
            }

            $po_items_data[] = [
                'purchase_order_id' => $purchaseOrder->id,
                'product_id'        => $product['id'],
                'product_name'      => $product['name'] ?? '',
                'product_sku'       => $product['sku'] ?? '',
                'ordered_quantity'  => $product['quantity'],
                'received_quantity' => 0,
                'quantity_returned' => 0,
                'unit_cost'         => $product['purchase_price'],
                'subtotal'          => $product['sub_total'] ?? $product['quantity'] * $product['purchase_price'],
                'discount_amount'   => 0,
                'discount_type'     => 'amount',
                'notes'             => null,
            ];
        }

        PurchaseOrderItem::insert($po_items_data);

        DB::commit();

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Tạo đơn đặt hàng thành công!');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()
            ->with('error', 'Lỗi khi tạo đơn đặt hàng: ' . $e->getMessage())
            ->withInput();
    }
}


    public function show($id)
    {
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

    public function cancel(string $id)
    {
        $purchaseOrder = PurchaseOrder::find($id);
        if ($purchaseOrder && $purchaseOrder->status_id != 5) {
            $purchaseOrder->status_id = 5;
            $purchaseOrder->save();
            // Thay vì redirect()->back(), chỉ định rõ route để redirect
            return redirect()->route('admin.purchase-orders.show', $id)->with('success', 'Đơn hàng đã được hủy thành công!');
        } else {
            return redirect()->route('admin.purchase-orders.show', $id)->with('error', 'Đơn hàng đã bị hủy hoặc hoàn thành!');
        }
    }

    public function approve(string $id)
    {
        $purchaseOrder = PurchaseOrder::find($id);
        if ($purchaseOrder && $purchaseOrder->status_id != 2) {
            $purchaseOrder->status_id = 2;
            $purchaseOrder->save();
            // Thay vì redirect()->back(), chỉ định rõ route để redirect
            return redirect()->route('admin.purchase-orders.show', $id)->with('success', 'Duyệt đơn thành công!');
        } else {
            return redirect()->route('admin.purchase-orders.show', $id)->with('error', 'Đơn hàng đã được duyệt!');
        }
    }

    public function edit(string $id)
    {
        // Lấy thông tin đơn hàng và các sản phẩm trong đơn hàng
        $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', '=', $id)->with('product')->get();
        $purchaseOrder = PurchaseOrder::where('id', '=', $id)->with('status')->get();

        // Kiểm tra trạng thái đơn hàng, chỉ cho phép chỉnh sửa nếu đơn hàng chưa được duyệt
        if ($purchaseOrder[0]->status_id != 1 && $purchaseOrder[0]->status_id != 2) {
            return redirect()->route('admin.purchase-orders.show', $id)
                ->with('error', 'Không thể chỉnh sửa đơn hàng đã được duyệt, đã nhập hàng hoặc đã hủy!');
        }

        // Lấy danh sách sản phẩm, nhà cung cấp và người dùng
        $productQuery = Product::query();
        $products = ['data' => $productQuery->get()];
        $supplierQuery = Supplier::query();
        $suppliers = $supplierQuery->get();
        $user = User::all();

        return Inertia::render('admin/purchase_orders/Edit', [
            'purchaseOrderItem' => $purchaseOrderItem,
            'purchaseOrder' => $purchaseOrder,
            'products' => $products,
            'suppliers' => $suppliers,
            'users' => $user,
        ]);
    }

   public function update(Request $request, string $id)
{
    $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'expected_import_date' => 'required|date',
        'products' => 'required|array|min:1',
        'products.*.id' => 'required|exists:products,id',
        'products.*.quantity' => 'required|numeric|min:1',
        'products.*.purchase_price' => 'required|numeric|min:0.01',
    ], [
        'expected_import_date.required' => 'Vui lòng chọn ngày nhập dự kiến.',
        'products.*.purchase_price.required' => 'Vui lòng nhập đơn giá cho từng sản phẩm.',
        'products.*.purchase_price.min' => 'Đơn giá phải lớn hơn 0.',
    ]);

    $order_date = now();

    // Validate ngày nhập dự kiến >= ngày đặt hàng
    if (Carbon::parse($request->expected_import_date)->lt($order_date->startOfDay())) {
        return back()->withErrors(['expected_import_date' => 'Ngày nhập dự kiến phải lớn hơn hoặc bằng ngày đặt hàng.']);
    }

    DB::beginTransaction();
    try {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status_id != 1 && $purchaseOrder->status_id != 2) {
            return redirect()->route('admin.purchase-orders.show', $id)
                ->with('error', 'Không thể cập nhật đơn hàng đã nhập hàng hoặc đã hủy!');
        }

        $purchaseOrder->update([
            'po_number' => $request->order_code ?? $purchaseOrder->po_number,
            'supplier_id' => $request->supplier_id,
            'expected_delivery_date' => $request->expected_import_date,
            'discount_type' => $request->discount['type'] ?? null,
            'discount_amount' => $request->discount['value'] ?? null,
            'total_amount' => $request->total_amount,
            'created_by' => $request->user_id ?? Auth::id(),
            'notes' => $request->note,
        ]);

        // Xóa và thêm lại items
        PurchaseOrderItem::where('purchase_order_id', $id)->delete();

        $po_items_data = [];
        foreach ($request->products as $product) {
            $po_items_data[] = [
                'purchase_order_id' => $id,
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
        PurchaseOrderItem::insert($po_items_data);

        DB::commit();
        return redirect()->route('admin.purchase-orders.show', $id)
            ->with('success', 'Cập nhật đơn hàng thành công!');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('admin.purchase-orders.edit', $id)
            ->with('error', 'Lỗi: ' . $e->getMessage());
    }
}

    public function getStatus($id)
    {
        $purchaseOrder = PurchaseOrder::with('status')->findOrFail($id);
        return response()->json([
            'status_id' => $purchaseOrder->status_id,
            'status_name' => $purchaseOrder->status->name,
            'status_code' => $purchaseOrder->status->code,
        ]);
    }

    public function getImportedQuantities($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $quantities = $purchaseOrder->items()->get(['id', 'product_id', 'product_name', 'ordered_quantity', 'received_quantity']);
        return response()->json($quantities);
    }
}
