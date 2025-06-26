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
use Illuminate\Support\Facades\Validator;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with(['purchaseOrder', 'supplier', 'createdBy'])->get();
        return Inertia::render('admin/batches/Index', [
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
                    'rejected_quantity',
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
            'discount_type',
            'discount_amount',
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
        $purchaseOrder = PurchaseOrder::with('status')->findOrFail($po_id);
        $purchaseOrderItems = PurchaseOrderItem::with('product')
            ->where('purchase_order_id', $po_id)
            ->get();

        return Inertia::render('admin/batches/Create', [
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderItem' => $purchaseOrderItems,
            'suppliers' => Supplier::all(),
            'users' => User::all(),
        ]);
    }

public function save(Request $request)
{
    try {
        // Validation
        $validator = Validator::make($request->all(), [
            'batch_items' => 'required|array',
            'batch_items.*.product_id' => 'required|integer|exists:products,id',
            'batch_items.*.purchase_order_item_id' => 'nullable|integer|exists:purchase_order_items,id',
            'batch_items.*.received_quantity' => 'required|integer|min:0',
            'batch_items.*.rejected_quantity' => 'nullable|integer|min:0',
            'batch_items.*.purchase_price' => 'required|numeric|min:0',
            'batch_items.*.total_amount' => 'required|numeric|min:0',
            'purchase_order_id' => 'required|integer|exists:purchase_orders,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'total_amount' => 'required|numeric|min:0',
            'discount.type' => 'nullable|in:amount,percent',
            'discount.value' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,partially_paid,unpaid',
            'payment_method' => 'required_if:payment_status,paid,partially_paid|nullable|in:cash,bank_transfer,credit_card',
            'payment_date' => 'required_if:payment_status,paid,partially_paid|nullable|date',
            'paid_amount' => 'required_if:payment_status,paid,partially_paid|numeric|min:0',
            'expected_import_date' => 'required|date',
            'batch_code' => 'nullable|string|max:50',
            'invoice_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            \Log::warning('Batch validation failed:', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }

        $user_id = $request->user_id ?? Auth::id();

        // Tạo batch_number
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

        // Tạo invoice_number
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

        // Tính receipt_status
        $totalOrdered = 0;
        $totalReceived = 0;
        $totalRejected = 0;
        $hasRejected = false;

        foreach ($request->batch_items as $item) {
            $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
            if (!$poItem) {
                \Log::error('Invalid purchase order item:', ['item' => $item]);
                return back()->withErrors(['batch_items' => "Mục đơn hàng của sản phẩm {$item['product_id']} không tồn tại."])->withInput();
            }

            $orderedQty = $poItem->ordered_quantity;
            $receivedQty = $item['received_quantity'] ?? 0;
            $rejectedQty = $item['rejected_quantity'] ?? 0;


            $totalOrdered += $orderedQty;
            $totalReceived += $receivedQty;
            $totalRejected += $rejectedQty;

            if ($rejectedQty > 0) {
                $hasRejected = true;
            }
        }

        // Xác định receipt_status
        if (!$hasRejected && $totalReceived >= $totalOrdered) {
            $receipt_status = 'completed';
        } else {
            $receipt_status = 'partially_received';
        }

        // Tạo batch
        $batch_data = [
            'batch_number' => $batch_number,
            'purchase_order_id' => $request->purchase_order_id,
            'supplier_id' => $request->supplier_id,
            'received_date' => $request->expected_import_date,
            'invoice_number' => $invoice_number,
            'total_amount' => $request->total_amount,
            'discount_type' => $request->discount['type'] ?? null,
            'discount_amount' => $request->discount['value'] ?? 0,
            'payment_status' => $request->payment_status,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'paid_amount' => $request->paid_amount,
            'payment_reference' => $request->payment_reference,
            'receipt_status' => $receipt_status,
            'created_by' => $user_id,
            'notes' => $request->notes,
        ];

        $batch = Batch::create($batch_data);

        // Lưu batch_items
        foreach ($request->batch_items as $item) {
            $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
            if (!$poItem) {
                \Log::error('Invalid purchase order item during batch item creation:', ['item' => $item]);
                return back()->withErrors(['batch_items' => "Mục đơn hàng của sản phẩm {$item['product_id']} không tồn tại."])->withInput();
            }

            $receivedQty = $item['received_quantity'] ?? 0;
            $rejectedQty = $item['rejected_quantity'] ?? 0;


            BatchItem::create([
                'batch_id' => $batch->id,
                'product_id' => $item['product_id'],
                'purchase_order_item_id' => $item['purchase_order_item_id'],
                'ordered_quantity' => $poItem->ordered_quantity,
                'received_quantity' => $receivedQty,
                'rejected_quantity' => $rejectedQty,
                'remaining_quantity' => max(0, $poItem->ordered_quantity - ($receivedQty + $rejectedQty)),
                'current_quantity' => $receivedQty,
                'purchase_price' => $item['purchase_price'],
                'total_amount' => $item['total_amount'],
                'manufacturing_date' => $item['manufacturing_date'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'inventory_status' => 'active',
                'created_by' => $user_id,
            ]);

            \Log::info('Batch item created:', [
                'batch_id' => $batch->id,
                'product_id' => $item['product_id'],
                'received_quantity' => $receivedQty,
                'rejected_quantity' => $rejectedQty,
            ]);
        }

        \Log::info('Batch created successfully:', ['batch_id' => $batch->id]);
        return redirect()->route('admin.batches.index')->with('success', 'Đã tạo đơn nhập hàng thành công.');
    } catch (\Exception $e) {
        \Log::error('Batch creation failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return back()->withErrors([
            'general' => 'Đã có lỗi xảy ra: ' . $e->getMessage(),
        ])->withInput();
    }
}

    public function create()
    {
        // Có thể thêm logic nếu cần
        return Inertia::render('admin/batches/Create');
    }

    public function store(Request $request)
    {
        // Có thể thêm logic nếu cần
        return $this->save($request);
    }
}