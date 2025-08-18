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
            'suppliers' => $suppliers,
            'users' => $user
        ]);
    }

    public function store(Request $request)
    {

        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.name' => ['required', 'string'],
            'products.*.sku' => ['required', 'string'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'products.*.sub_total' => ['required', 'numeric', 'min:0'],
            'discount.type' => ['nullable', 'in:amount,percent'],
            'discount.value' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric'],
            // 'total_amount' => ['required', 'numeric'],
            'user_id' => ['required', 'exists:users,id'],
            'expected_import_date' => ['required', 'date','after_or_equal:today'],
            'order_code' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ], [
            'supplier_id.required' => 'Nhà cung cấp không được để trống.',
            'supplier_id.exists' => 'Nhà cung cấp không hợp lệ.',
            'products.required' => 'Phải chọn ít nhất một sản phẩm.',
            'products.array' => 'Danh sách sản phẩm không hợp lệ.',
            'products.*.id.required' => 'Thiếu thông tin sản phẩm.',
            'products.*.id.exists' => 'Sản phẩm không tồn tại.',
            'products.*.name.required' => 'Tên sản phẩm không được để trống.',
            'products.*.sku.required' => 'SKU sản phẩm không được để trống.',
            'products.*.quantity.required' => 'Số lượng sản phẩm không được để trống.',
            'products.*.quantity.integer' => 'Số lượng sản phẩm phải là số nguyên.',
            'products.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
            'products.*.purchase_price.required' => 'Đơn giá sản phẩm không được để trống.',
            'products.*.purchase_price.numeric' => 'Đơn giá sản phẩm phải là số.',
            'products.*.purchase_price.min' => 'Đơn giá sản phẩm phải >= 0.',
            'products.*.sub_total.required' => 'Thành tiền không được để trống.',
            'products.*.sub_total.numeric' => 'Thành tiền phải là số.',
            'products.*.sub_total.min' => 'Thành tiền phải >= 0.',
            'discount.type.in' => 'Loại chiết khấu không hợp lệ.',
            'discount.value.numeric' => 'Giá trị chiết khấu phải là số.',
            'discount.value.min' => 'Giá trị chiết khấu phải >= 0.',
            'total_amount.required' => 'Tổng tiền không được để trống.',
            'total_amount.numeric' => 'Tổng tiền phải là số.',
            // 'total_amount.min' => 'Tổng tiền phải >= 0.',
            'user_id.required' => 'Nhân viên phụ trách không được để trống.',
            'user_id.exists' => 'Nhân viên phụ trách không hợp lệ.',
            'expected_import_date.required' => 'Ngày nhập dự kiến không được để trống.',
            'expected_import_date.date' => 'Ngày nhập dự kiến không hợp lệ.',
            'expected_import_date.after_or_equal' => 'Ngày nhập dự kiến phải lớn hơn hoặc bằng ngày đặt.',        ]);

        $user_id = $validated['user_id'];
        // 2. Xác định po_number (order_code)
        $po_number = $validated['order_code'] ?? null;
        if ($po_number === null) {
            $today = Carbon::now()->format('Ymd');
            $prefix = "PO-{$today}-";
            $lastPo = PurchaseOrder::withTrashed()
                ->where('po_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING_INDEX(po_number, "-", -1) AS UNSIGNED) DESC')
                ->first();
            if ($lastPo) {
                $lastNumber = (int) substr($lastPo->po_number, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }
            $po_number = "{$prefix}{$nextNumber}";
        }

        $po_data = [
            'po_number' => $po_number,
            'supplier_id' => $validated['supplier_id'],
            'status_id' => 1,
            'order_date' => now(),
            'expected_delivery_date' => $validated['expected_import_date'],
            'actual_delivery_date' => null,
            'discount_type' => $validated['discount']['type'] ?? null,
            'discount_amount' => $validated['discount']['value'] ?? null,
            'total_amount' => $validated['total_amount'],
            'created_by' => $user_id,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $validated['note'] ?? null,
        ];

        $purchaseOrder = PurchaseOrder::create($po_data);
        $purchaseOrderId = $purchaseOrder->id;

        $po_items_data = [];
        foreach ($validated['products'] as $product) {
            $dbProduct = Product::find($product['id']);
            if (!$dbProduct || !$dbProduct->is_active) {
                return back()->withErrors(['products' => "Sản phẩm {$product['name']} đã bị ẩn và không thể nhập hàng."]);
            }
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
        // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
        DB::beginTransaction();

        try {
            // Lấy thông tin đơn hàng hiện tại
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Kiểm tra trạng thái đơn hàng, chỉ cho phép cập nhật nếu đơn hàng chưa được duyệt hoặc đã duyệt
            if ($purchaseOrder->status_id != 1 && $purchaseOrder->status_id != 2) {
                return redirect()->route('admin.purchase-orders.show', $id)
                    ->with('error', 'Không thể cập nhật đơn hàng đã nhập hàng hoặc đã hủy!');
            }
            // ✅ Validate dữ liệu đầu vào
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.name' => ['required', 'string'],
            'products.*.sku' => ['required', 'string'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'products.*.sub_total' => ['required', 'numeric', 'min:0'],
            'discount.type' => ['nullable', 'in:amount,percent'],
            'discount.value' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric'],
            'user_id' => ['required', 'exists:users,id'],
            // 🚀 check ngày nhập dự kiến >= ngày đặt (order_date trong DB)
            'expected_import_date' => ['required', 'date', 'after_or_equal:' . $purchaseOrder->order_date],
            'order_code' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ], [
            'expected_import_date.after_or_equal' => 'Ngày nhập dự kiến phải lớn hơn hoặc bằng ngày đặt hàng (' . $purchaseOrder->order_date . ').',
        ]);

            // Cập nhật thông tin đơn hàng
            $purchaseOrder->po_number = $request->order_code ?? $purchaseOrder->po_number;
            $purchaseOrder->supplier_id = $request->supplier_id;
            $purchaseOrder->expected_delivery_date = $request->expected_import_date;
            $purchaseOrder->discount_type = $request->discount['type'] ?? null;
            $purchaseOrder->discount_amount = $request->discount['value'] ?? null;
            $purchaseOrder->total_amount = $request->total_amount;
            $purchaseOrder->created_by = $request->user_id ?? Auth::id();
            $purchaseOrder->notes = $request->note;
            $purchaseOrder->save();

            // Xóa tất cả các item hiện tại của đơn hàng
            PurchaseOrderItem::where('purchase_order_id', $id)->delete();

            // Thêm lại các item mới
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

            // Insert nhiều bản ghi vào bảng purchase_order_items
            PurchaseOrderItem::insert($po_items_data);

            // Commit transaction
            DB::commit();

            // Redirect về trang chi tiết đơn hàng với thông báo thành công
            return redirect()->route('admin.purchase-orders.show', $id)
                ->with('success', 'Cập nhật đơn hàng thành công!');

        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();

            // Redirect về trang chỉnh sửa với thông báo lỗi
            return redirect()->route('admin.purchase-orders.edit', $id)
                ->with('error', 'Đã xảy ra lỗi khi cập nhật đơn hàng: ' . $e->getMessage());
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
