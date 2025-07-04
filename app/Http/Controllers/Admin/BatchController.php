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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BatchController extends Controller
{
    public function index()
    {
        $emptyBatchIds = Batch::whereDoesntHave('batchItems', function ($q) {
            $q->where('current_quantity', '>', 0);
        })->pluck('id');


        if ($emptyBatchIds->count() > 0) {
            // Nếu chưa có ON DELETE CASCADE thì xóa batchItems trước
            BatchItem::whereIn('batch_id', $emptyBatchIds)->delete();
            Batch::whereIn('id', $emptyBatchIds)->delete();
            Log::info('Deleted batches:', $emptyBatchIds->toArray());
        }

        $batches = Batch::with([
            'purchaseOrder',
            'supplier',
            'creator:id,name',
            'batchItems' => function ($query) {
                $query->with('product', function ($productQuery) {
                    $productQuery->select('id','name', 'sku', 'unit_id')
                        ->with('unit:id,name');
                });
            }
        ])
            ->orderBy('id', 'desc')
            ->get();
        return Inertia::render('admin/batches/Index', [
            'batches' => $batches,
        ]);
    }

    public function show($id)
    {
        try {
            // Fetch the batch with related supplier
            $batch = Batch::with(['supplier', 'creator'])
                ->where('id', $id)
                ->firstOrFail();

            // Fetch batch items with product details
            $batchItems = BatchItem::with([
                'product' => function ($query) {
                    $query->select('id', 'name', 'sku', 'image_url')
                        ->with([
                            'unit' => function ($query) {
                                $query->select('id', 'name');
                            }
                        ]);
                }
            ])
                ->where('batch_id', $id)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'batch_id' => $item->batch_id,
                        'product_id' => $item->product_id,
                        'purchase_order_item_id' => $item->purchase_order_item_id,
                        'product_name' => $item->product->name ?? $item->product_name,
                        'product_sku' => $item->product->sku ?? $item->product_sku,
                        'ordered_quantity' => $item->ordered_quantity,
                        'received_quantity' => $item->received_quantity,
                        'rejected_quantity' => $item->rejected_quantity,
                        'remaining_quantity' => $item->remaining_quantity,
                        'current_quantity' => $item->current_quantity,
                        'purchase_price' => $item->purchase_price,
                        'total_amount' => $item->total_amount,
                        'manufacturing_date' => $item->manufacturing_date,
                        'expiry_date' => $item->expiry_date,
                        'inventory_status' => $item->inventory_status,
                        'product' => [
                            'name' => $item->product->name ?? $item->product_name,
                            'sku' => $item->product->sku ?? $item->product_sku,
                            'image_url' => $item->product->image_url ?? null,
                            'unit' => $item->product->unit ? [
                                'name' => $item->product->unit->name
                            ] : null,
                        ],
                    ];
                });

            // Fetch suppliers
            $suppliers = Supplier::select('id', 'name', 'email', 'phone', 'address')
                ->get()
                ->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'address' => $supplier->address,
                        'pivot' => [
                            'purchase_price' => $supplier->pivot->purchase_price ?? 0,
                        ],
                    ];
                });

            // Fetch users
            $users = User::select('id', 'name')->get();

            return Inertia::render('admin/batches/Show', [
                'batch' => [$batch], // Wrap in array to match Vue component props
                'batchItem' => $batchItems,
                'suppliers' => $suppliers,
                'users' => $users,
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching batch details: ' . $e->getMessage());
            return Inertia::render('admin/batches/Show', [
                'batch' => [],
                'batchItem' => [],
                'suppliers' => [],
                'users' => [],
                'flash' => [
                    'error' => 'Không thể tải thông tin lô hàng. Vui lòng thử lại.',
                ],
            ]);
        }
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
                Log::warning('Batch validation failed:', $validator->errors()->toArray());
                return back()->withErrors($validator)->withInput();
            }

            $user_id = $request->user_id ?? Auth::id();

            // Tạo batch_number
            $batch_number = null;
            if ($batch_number === null) {
                $today = Carbon::now()->format('Ymd');
                $prefix = "REC-{$today}-";
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
                    Log::error('Invalid purchase order item:', ['item' => $item]);
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

            $paymentStatus = '';
            $remainingAmount = 0;

            if ($request->paid_amount < $request->total_amount && $request->paid_amount > 0) {
                $paymentStatus = 'partially_paid';
                $remainingAmount = $request->total_amount - $request->paid_amount;
            } elseif ($request->paid_amount === $request->total_amount) {
                $paymentStatus = 'paid';
                $remainingAmount = 0;
            } else {
                $paymentStatus = 'unpaid';
                $remainingAmount = $request->total_amount;
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
                'payment_status' => $paymentStatus,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $remainingAmount,
                'payment_reference' => $request->payment_reference,
                'receipt_status' => $receipt_status,
                'created_by' => $user_id,
                'notes' => $request->notes,
            ];

            $batch = Batch::create($batch_data);

            // Lưu batch_items và cập nhật purchase_order_items
            foreach ($request->batch_items as $item) {
                $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                if (!$poItem) {
                    Log::error('Invalid purchase order item during batch item creation:', ['item' => $item]);
                    return back()->withErrors(['batch_items' => "Mục đơn hàng của sản phẩm {$item['product_id']} không tồn tại."])->withInput();
                }

                $orderedQty = $poItem->ordered_quantity;

                $receivedQty = $item['received_quantity'] ?? 0;
                $rejectedQty = $item['rejected_quantity'] ?? null;
                $remainingQty = 0;

                if ($rejectedQty === null) {
                    $rejectedQty = max(0, $orderedQty - $receivedQty);
                }

                if ($receivedQty < $orderedQty) {
                    $remainingQty = $rejectedQty;
                } else {
                    $remainingQty = 0; // Nếu đã nhận đủ hoặc hơn, không còn số lượng còn lại
                }

                BatchItem::create([
                    'batch_id' => $batch->id,
                    'product_id' => $item['product_id'],
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'ordered_quantity' => $poItem->ordered_quantity,
                    'received_quantity' => $receivedQty,
                    'rejected_quantity' => $rejectedQty,
                    'remaining_quantity' => $remainingQty,
                    'current_quantity' => $receivedQty,
                    'purchase_price' => $item['purchase_price'],
                    'total_amount' => $item['total_amount'],
                    'manufacturing_date' => $item['manufacturing_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'inventory_status' => 'active',
                    'created_by' => $user_id,
                ]);

                // Cập nhật received_quantity
                $poItem->updateReceivedQuantity();

                Log::info('Batch item created:', [
                    'batch_id' => $batch->id,
                    'product_id' => $item['product_id'],
                    'received_quantity' => $receivedQty,
                    'rejected_quantity' => $rejectedQty,
                ]);
            }

            // Cập nhật trạng thái purchase_order
            $purchaseOrder = PurchaseOrder::find($request->purchase_order_id);
            $purchaseOrder->updateStatusBasedOnItems();

            Log::info('Batch created successfully:', ['batch_id' => $batch->id]);
            return redirect()->route('admin.batches.index')->with('success', 'Đã tạo đơn nhập hàng thành công.');
        } catch (\Exception $e) {
            Log::error('Batch creation failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors([
                'general' => 'Đã có lỗi xảy ra: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    public function pay(Request $request, $id)
    {
        $request->validate([
            'paymentAmount' => 'required|numeric|min:0',
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|string|max:100',
        ]);

        $batch = Batch::with('batchItems')->findOrFail($id);

        // Tính tổng tiền cần thanh toán sau chiết khấu
        $subtotal = $batch->batchItems->sum('total_amount');

        if ($batch->discount_type === 'amount') {
            $subtotal -= $batch->discount_amount;
        } elseif ($batch->discount_type === 'percent') {
            $subtotal -= ($subtotal * $batch->discount_amount) / 100;
        }

        // Cộng thêm số tiền thanh toán mới
        $newPaidAmount = $batch->paid_amount + $request->paymentAmount;

        // Xác định trạng thái thanh toán
        if ($newPaidAmount >= $subtotal) {
            $paymentStatus = 'paid';
        } elseif ($newPaidAmount > 0) {
            $paymentStatus = 'partially_paid';
        } else {
            $paymentStatus = 'unpaid';
        }

        // Xác định trạng thái nhận hàng dựa vào thanh toán
        $newReceiptStatus = $batch->receipt_status;

        if ($paymentStatus === 'paid') {
            $newReceiptStatus = 'completed';
        } elseif ($paymentStatus === 'partially_paid') {
            $newReceiptStatus = 'partially_received';
        }
        
        Log::info('Received paid_amount:', [
            'raw' => $request->input('paid_amount'),
            'converted' => (float) str_replace('.', '', $request->paid_amount)
        ]);

        // Cập nhật Batch
        $batch->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'receipt_status' => $newReceiptStatus,
            'payment_date' => $request->paymentDate,
            'payment_method' => $request->paymentMethod,
        ]);

        return Inertia::location(route('admin.batches.show', $batch->id));
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
