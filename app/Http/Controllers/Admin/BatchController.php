<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\BatchItem;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with(['purchaseOrder', 'supplier', 'createdBy'])->get(); // Eager load the supplier
        return inertia('admin/batches/Index', [
            'batches' => $batches,
        ]);
    }
    public function show($id)
    {
        $batch = Batch::with([
            'batchItems' => function ($query) {
                $query->with([
                    'product' => function ($productQuery) {
                        $productQuery->select(
                            'id',
                            'name',
                            'sku',
                            'barcode',
                            'unit_id',
                            'description',
                            'selling_price',
                            'image_url'
                        );
                    },
                    'product.unit' => function ($unitQuery) {
                        $unitQuery->select('id', 'name');
                    },
                    'createdBy' => function ($userQuery) {
                        $userQuery->select('id', 'name', 'email');
                    },
                    'updatedBy' => function ($userQuery) {
                        $userQuery->select('id', 'name', 'email');
                    },
                    'purchaseOrderItem' => function ($poItemQuery) {
                        $poItemQuery->select(
                            'id',
                            'ordered_quantity',
                            'unit_cost'
                        );
                    }
                ])->select(
                        'id',
                        'batch_id',
                        'product_id',
                        'purchase_order_item_id',
                        'ordered_quantity',
                        'received_quantity',
                        'remaining_quantity',
                        'current_quantity',
                        'purchase_price',
                        'total_amount',
                        'manufacturing_date',
                        'expiry_date',
                        'inventory_status',
                        'created_by',
                        'updated_by',
                        'created_at',
                        'updated_at'
                    );
            },
            'supplier' => function ($query) {
                $query->select('id', 'name', 'contact_person', 'email', 'phone', 'address');
            },
            'purchaseOrder' => function ($query) {
                $query->select(
                    'id',
                    'po_number',
                    'order_date',
                    'expected_delivery_date',
                    'actual_delivery_date',
                    'total_amount'
                );
            },
            'createdBy' => function ($query) {
                $query->select('id', 'name', 'email');
            },
            'updatedBy' => function ($query) {
                $query->select('id', 'name', 'email');
            },
        ])->select(
                'id',
                'batch_number',
                'purchase_order_id',
                'supplier_id',
                'received_date',
                'invoice_number',
                'total_amount',
                'payment_status',
                'paid_amount',
                'receipt_status',
                'notes',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at'
            )->findOrFail($id);
        return Inertia::render('admin/batches/Show', [
            'batch' => $batch,
        ]);
    }
    public function add(Request $request, $po_id)
    {
        // Lấy đơn đặt hàng (PurchaseOrder) theo ID, kèm trạng thái
        $purchaseOrder = PurchaseOrder::with('status')->findOrFail($po_id);

        // Lấy các sản phẩm đã đặt trong PO
        $purchaseOrderItems = PurchaseOrderItem::with('product')
            ->where('purchase_order_id', $po_id)
            ->get();

        // Truy vấn danh sách sản phẩm, có thể lọc theo tên hoặc SKU nếu có từ khóa tìm kiếm
        $query = Product::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Lấy toàn bộ danh sách sản phẩm (không phân trang)
        $products = [
            'data' => $query->get(),
            'total' => $query->count(),
        ];

        // Trả dữ liệu về Inertia page
        return Inertia::render('admin/batches/Create', [
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderItem' => $purchaseOrderItems,
            'products' => $products,
            'suppliers' => Supplier::all(),
            'users' => User::all(),
        ]);
    }

public function save(Request $request)
{

    $user_id = $request->user_id ?? Auth::id();

    // Tạo batch_number nếu không có
    $batch_number = $request->batch_code;
    if ($batch_number === null) {
        $today = Carbon::now()->format('Ymd');
        $prefix = "RCPT-{$today}-";
        $lastBatch = Batch::where('batch_number', 'like', "{$prefix}%")
            ->orderByDesc('batch_number')
            ->first();
        $nextNumber = $lastBatch
            ? str_pad((int) substr($lastBatch->batch_number, -3) + 1, 3, '0', STR_PAD_LEFT)
            : '001';
        $batch_number = "{$prefix}{$nextNumber}";
    }

    // Tạo invoice_number nếu không có
    $invoice_number = $request->invoice_code;
    if ($invoice_number === null) {
        $today = Carbon::now()->format('Ymd');
        $prefix = "INV-{$today}-";
        $lastInvoice = Batch::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->first();
        $nextNumber = $lastInvoice
            ? str_pad((int) substr($lastInvoice->invoice_number, -3) + 1, 3, '0', STR_PAD_LEFT)
            : '001';
        $invoice_number = "{$prefix}{$nextNumber}";
    }

    // Tạo mảng dữ liệu lưu vào DB
    $batch_data = [
        'batch_number' => $batch_number,
        'purchase_order_id' => $request->purchase_order_id,
        'supplier_id' => $request->supplier_id,
        'received_date' => $request->expected_import_date,
        'invoice_number' => $invoice_number,
        'total_amount' => $request->total_amount,
        'payment_status' => $request->payment_status,
        'payment_method' => $request->payment_method,
        'payment_date' => $request->payment_date,
        'paid_amount' => $request->paid_amount,
        'payment_reference' => $request->payment_reference,
        'receipt_status' => $request->receipt_status,
        'created_by' => $user_id,
        'notes' => $request->notes,
    ];

    Batch::create($batch_data);

    return redirect()->route('admin.batches.index')->with('success', 'Đã tạo đơn nhập hàng thành công.');
}
    public function create()
    {
    }

    public function store(Request $request)
    {

    }

}
