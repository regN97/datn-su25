<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\BatchItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\BatchRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;

class BatchController extends Controller
{

    public function index()
    {
        // Xóa các batch có tổng current_quantity = 0
        $emptyBatchIds = Batch::whereHas('batchItems', function ($query) {
            $query->select('batch_id')
                ->groupBy('batch_id')
                ->havingRaw('SUM(current_quantity) = 0');
        })->pluck('id');

        if ($emptyBatchIds->count() > 0) {
            BatchItem::whereIn('batch_id', $emptyBatchIds)->delete();
            Batch::whereIn('id', $emptyBatchIds)->delete();
        }

        $batchesQuery = Batch::with([
            'purchaseOrder' => fn($query) => $query->select('id', 'po_number', 'supplier_id'),
            'supplier' => fn($query) => $query->select('id', 'name'),
            'creator' => fn($query) => $query->select('id', 'name'),
            'batchItems' => fn($query) => $query->with([
                'product' => fn($productQuery) => $productQuery->select('id', 'name', 'sku', 'unit_id')
                    ->with('unit:id,name')
            ])->select('id', 'batch_id', 'product_id', 'purchase_order_item_id', 'ordered_quantity', 'received_quantity', 'rejected_quantity', 'remaining_quantity', 'current_quantity', 'purchase_price', 'total_amount', 'manufacturing_date', 'expiry_date', 'inventory_status'),
        ])->orderBy('id', 'desc');

        $searchTerm = request()->input('search');
        $filterPaymentStatus = request()->input('payment_status');
        $filterReceiptStatus = request()->input('receipt_status');
        $filterInvoiceNumber = request()->input('invoice_number');
        $filterStartDate = request()->input('start_date');
        $filterEndDate = request()->input('end_date');

        if ($searchTerm) {
            $batchesQuery->where(function ($query) use ($searchTerm) {
                $query->where('batch_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('supplier', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhere('notes', 'like', "%{$searchTerm}%");
            });
        }

        if ($filterInvoiceNumber) {
            $batchesQuery->where('invoice_number', 'like', "%{$filterInvoiceNumber}%");
        }

        if ($filterPaymentStatus) {
            $batchesQuery->where('payment_status', $filterPaymentStatus);
        }

        if ($filterReceiptStatus) {
            $batchesQuery->where('receipt_status', $filterReceiptStatus);
        }

        if ($filterStartDate) {
            $batchesQuery->where('received_date', '>=', $filterStartDate);
        }

        if ($filterEndDate) {
            $batchesQuery->where('received_date', '<=', $filterEndDate . ' 23:59:59');
        }

        $batches = $batchesQuery->get();

        // Lấy tổng tiền từ trường total_amount đã tính sẵn trong bảng batches
        $totalAmount = $batches->sum('total_amount');

        return Inertia::render('admin/batches/Index', [
            'batches' => $batches,
            'suppliers' => Supplier::select('id', 'name')->get(),
            'purchaseOrders' => PurchaseOrder::select('id', 'po_number', 'supplier_id')->get(),
            'importMessage' => session('importMessage'),
            'importStatus' => session('importStatus'),
            'total_amount' => $totalAmount,
            'filters' => [
                'search' => $searchTerm,
                'payment_status' => $filterPaymentStatus,
                'receipt_status' => $filterReceiptStatus,
                'invoice_number' => $filterInvoiceNumber,
                'start_date' => $filterStartDate,
                'end_date' => $filterEndDate,
            ],
        ]);
    }


    public function show($id)
    {
        try {
            // Fetch the batch with related supplier
            $batch = Batch::with(['supplier', 'creator', 'purchaseOrder'])
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
                'batch' => $batch, // Wrap in array to match Vue component props
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
            'user' => auth()->user(),
        ]);
    }

    public function save(BatchRequest $request)
    {
        try {
            $user_id = $request->user_id ?? Auth::id();

            return DB::transaction(function () use ($request, $user_id) {
                // Tạo batch_number tự động
                $today = Carbon::now()->format('Ymd');
                $batch_number = $this->generateBatchNumber();

                // Tạo invoice_number tự động
                $prefixInvoice = "INV-{$today}-";
                $lastInvoice = Batch::where('invoice_number', 'like', "{$prefixInvoice}%")
                    ->orderByDesc('invoice_number')
                    ->first();
                $nextNumberInv = $lastInvoice
                    ? str_pad((int) substr($lastInvoice->invoice_number, -3) + 1, 3, '0', STR_PAD_LEFT)
                    : '001';
                $invoice_number = $request->invoice_code ?? "{$prefixInvoice}{$nextNumberInv}";

                // Kiểm tra số lượng nhận trước khi tạo batch
                foreach ($request->batch_items as $item) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    if (!$poItem) {
                        throw new \Exception("Mục đơn hàng của sản phẩm {$item['product_id']} không tồn tại.");
                    }

                    $orderedQty = $poItem->ordered_quantity;
                    $receivedQty = $item['received_quantity'] ?? 0;

                    // Kiểm tra số lượng nhận không được vượt quá số lượng đặt
                    if ($receivedQty > $orderedQty) {
                        throw new \Exception("Số lượng nhận ({$receivedQty}) của sản phẩm {$item['product_id']} không được vượt quá số lượng đặt ({$orderedQty}).");
                    }
                }

                // Tính trạng thái nhận hàng
                $totalOrdered = 0;
                $totalReceived = 0;
                $totalRejected = 0;
                $hasRejected = false;

                foreach ($request->batch_items as $item) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    if ($poItem) {
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
                }

                // Xác định trạng thái nhận hàng
                $received_status = 'completed';

                // Tính trạng thái thanh toán
                $paymentStatus = '';
                $remainingAmount = 0;
                if ($request->paid_amount < $request->total_amount && $request->paid_amount > 0) {
                    $paymentStatus = 'partially_paid';
                    $remainingAmount = $request->total_amount - $request->paid_amount;
                } elseif ($request->paid_amount == $request->total_amount) {
                    $paymentStatus = 'paid';
                    $remainingAmount = 0;
                } else {
                    $paymentStatus = 'unpaid';
                    $remainingAmount = $request->total_amount;
                }

                // Tạo batch
                $batch = Batch::create([
                    'batch_number' => $batch_number,
                    'purchase_order_id' => $request->purchase_order_id,
                    'supplier_id' => $request->supplier_id,
                    'received_date' => $request->import_date,
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
                    'receipt_status' => $received_status,
                    'status' => 'completed',
                    'created_by' => $user_id,
                    'notes' => $request->notes,
                ]);

                // Lặp qua từng sản phẩm
                foreach ($request->batch_items as $item) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    if (!$poItem) {
                        throw new \Exception("Mục đơn hàng của sản phẩm {$item['product_id']} không tồn tại.");
                    }

                    // Tính tổng số lượng đã nhận từ các batch trước
                    $totalPreviousReceived = BatchItem::where('purchase_order_item_id', $item['purchase_order_item_id'])
                        ->where('product_id', $item['product_id'])
                        ->sum('received_quantity');

                    $orderedQty = $poItem->ordered_quantity;
                    $receivedQty = $item['received_quantity'] ?? 0;
                    $rejectedQty = $item['rejected_quantity'] ?? 0;

                    // Tổng số lượng đã nhận sau lần nhập này
                    $newTotalReceived = $totalPreviousReceived + $receivedQty;

                    // Tính remaining_quantity dựa trên tổng số lượng đã nhận
                    $remainingQty = max(0, $orderedQty - $newTotalReceived);

                    // Kiểm tra batch_item đã tồn tại
                    $existingBatchItem = BatchItem::where('purchase_order_item_id', $item['purchase_order_item_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if ($existingBatchItem) {
                        // Nếu đã có thì update các trường
                        $existingBatchItem->update([
                            'ordered_quantity' => $orderedQty,
                            'received_quantity' => $newTotalReceived,      // Cập nhật tổng số lượng đã nhận
                            'rejected_quantity' => $rejectedQty,                      // Reset về 0 vì đã nhận bù
                            'remaining_quantity' => $remainingQty,
                            'current_quantity' => $newTotalReceived,      // current = tổng số đã nhận
                            'purchase_price' => $item['purchase_price'],
                            'total_amount' => $item['total_amount'],
                            'manufacturing_date' => $item['manufacturing_date'] ?? null,
                            'expiry_date' => $item['expiry_date'] ?? null,
                            'inventory_status' => 'active',
                            'updated_by' => $user_id,
                        ]);
                    } else {
                        // Nếu chưa có thì tạo mới
                        BatchItem::create([
                            'batch_id' => $batch->id,
                            'product_id' => $item['product_id'],
                            'purchase_order_item_id' => $item['purchase_order_item_id'],
                            'ordered_quantity' => $orderedQty,
                            'received_quantity' => $receivedQty,
                            'rejected_quantity' => $rejectedQty,
                            'remaining_quantity' => $remainingQty,
                            'current_quantity' => $receivedQty,
                            'purchase_price' => $item['purchase_price'],
                            'total_amount' => $item['total_amount'],
                            'manufacturing_date' => $item['manufacturing_date'] ?? null,
                            'expiry_date' => $item['expiry_date'] ?? null,
                            'inventory_status' => 'active',
                            'status' => 'completed',
                            'created_by' => $user_id,
                        ]);
                    }

                    // Cập nhật tồn kho sản phẩm
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        // Cập nhật số lượng tồn kho và ngày nhập gần nhất
                        $previousStock = $product->stock_quantity;
                        $changeQty = $receivedQty; // hoặc một biến khác tùy context
                        $newStock = $previousStock + $changeQty;
                        // Cập nhật tồn kho sản phẩm
                        $product->update([
                            'stock_quantity' => $newStock,
                            'last_received_at' => now(), // nếu có cột này
                        ]);

                        // Ghi lại lịch sử biến động kho
                        InventoryTransaction::create([
                            'transaction_type_id' => 1,
                            'product_id' => $product->id,
                            'quantity_change' => +$receivedQty,
                            'unit_price' => $item['purchase_price'],
                            'total_value' => $item['purchase_price'] * $receivedQty,
                            'transaction_date' => now(),
                            'related_batch_id' => $batch->id,
                            'user_id' => $user_id,
                            'stock_after' => $newStock,
                            'note' => 'Nhập hàng từ phiếu ' . $batch->batch_number
                        ]);
                    }

                    // Cập nhật số lượng đã nhận của PurchaseOrderItem
                    $poItem->updateReceivedQuantity();
                }

                // Cập nhật trạng thái đơn hàng
                $purchaseOrder = PurchaseOrder::find($request->purchase_order_id);
                $purchaseOrder->updateStatusBasedOnItems();

                return redirect()
                    ->route('admin.batches.index')
                    ->with('success', 'Đã tạo đơn nhập hàng và ghi lịch sử tồn kho thành công.');
            });
        } catch (\Exception $e) {
            Log::error('Batch creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        // Xác định receipt_status dựa vào payment_status
        $newReceiptStatus = $batch->receipt_status;
        if ($paymentStatus === 'paid') {
            $newReceiptStatus = 'completed';
        } else {
            $newReceiptStatus = 'pending';  // Sửa từ 'partially_received' thành 'pending'
        }

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

        return Inertia::render('admin/batches/Add', [
            'products' => $products,
            'suppliers' => $suppliers,
            'user' => $user
        ]);
    }

    public function store(BatchRequest $request)
    {
        DB::beginTransaction();
        try {
            // Tạo batch_number tự động
            $today = Carbon::now()->format('Ymd');

            $batch_number = $this->generateBatchNumber();

            // Tạo invoice_number tự động
            $prefixInvoice = "INV-{$today}-";
            $lastInvoice = Batch::where('invoice_number', 'like', "{$prefixInvoice}%")
                ->orderByDesc('invoice_number')
                ->first();
            $nextNumberInv = $lastInvoice
                ? str_pad((int) substr($lastInvoice->invoice_number, -3) + 1, 3, '0', STR_PAD_LEFT)
                : '001';
            $invoice_number = $request->invoice_code ?? "{$prefixInvoice}{$nextNumberInv}";

            // Tính trạng thái thanh toán
            $paidAmount = (float) $request->paid_amount;
            $totalAmount = (float) $request->total_amount;

            if ($paidAmount > 0 && $paidAmount < $totalAmount) {
                $paymentStatus = 'partially_paid';
            } elseif ($paidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            } else {
                $paymentStatus = 'unpaid';
            }
            $batch = Batch::create([
                'batch_number' => $batch_number,
                'invoice_number' => $invoice_number,
                'purchase_order_id' => null, // không có PO
                'supplier_id' => $request['supplier_id'],
                'total_amount' => $request['total_amount'],
                'discount_type' => $request->discount['type'] ?? null,
                'discount_amount' => $request->discount['value'] ?? 0,
                'payment_reference' => $request->payment_reference,
                'received_date' => $request->import_date,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'paid_amount' => $request->paid_amount,
                'payment_status' => $paymentStatus,
                'status' => 'draft', // nháp
                'receipt_status' => 'pending',
                'created_by' => Auth::id(),
                'note' => $request->note,
            ]);

            foreach ($request['batch_items'] as $item) {

                BatchItem::create([
                    'batch_id' => $batch->id,
                    'product_id' => $item['product_id'],
                    'received_quantity' => $item['received_quantity'],
                    'remaining_quantity' => 0,
                    'purchase_price' => $item['purchase_price'],
                    'total_amount' => $item['total_amount'],
                    'manufacturing_date' => $item['manufacturing_date'],
                    'expiry_date' => $item['expiry_date'],
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();
            return redirect()
                ->route('admin.batches.show', $batch->id)
                ->with('success', 'Đã tạo đơn nhập hàng thành công.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Duyệt đơn nhập hàng - chuyển từ draft sang completed
     */
    public function edit(Batch $batch)
    {
        $batchItem = BatchItem::where('batch_id', $batch->id)
            ->with('product')
            ->get();

        $batch = Batch::find($batch->id);

        // Kiểm tra trạng thái nhập hàng, chỉ cho phép chỉnh sửa nếu đơn nhập là draft
        if ($batch->status !== 'draft') {
            return redirect()->route('admin.batches.show', $batch->id)
                ->with('error', 'Không thể chỉnh sửa đơn nhập hàng đã được duyệt!');
        }

        // Lấy danh sách sản phẩm, nhà cung cấp và người dùng
        $products = ['data' => Product::all()];
        $suppliers = Supplier::all();
        $users = User::all();

        return Inertia::render('admin/batches/Edit', [
            'batchItem' => $batchItem,
            'batch' => $batch,
            'products' => $products,
            'suppliers' => $suppliers,
            'users' => $users,
        ]);
    }

    public function update(BatchRequest $request, Batch $batch)
    {
        DB::beginTransaction();
        try {
            // Chỉ cho phép sửa khi còn draft
            if ($batch->status !== 'draft') {
                return redirect()->route('admin.batches.show', $batch->id)
                    ->with('error', 'Không thể chỉnh sửa đơn nhập hàng đã được duyệt!');
            }

            // Tính toán trạng thái thanh toán mới
            $totalAmount = $request->total_amount;
            $paidAmount = $batch->paid_amount ?? 0;

            // Xác định payment_status dựa trên total_amount mới
            if ($totalAmount == 0) {
                $paymentStatus = 'paid';
            } else if ($paidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            } else if ($paidAmount > 0) {
                $paymentStatus = 'partially_paid';
            } else {
                $paymentStatus = 'unpaid';
            }

            // Cập nhật thông tin batch
            $batch->update([
                'batch_number' => $request->batch_code ?? $batch->batch_number,
                'manufacturing_date' => $batch->manufacturing_date,
                'expiry_date' => $batch->expiry_date,
                'supplier_id' => $request->supplier_id,
                'import_date' => $request->import_date,
                'discount_type' => $request->discount_type,
                'discount_amount' => $request->discount_amount,
                'total_amount' => $request->total_amount,
                'payment_status' => $paymentStatus,
                'status' => 'draft',
                'receipt_status' => 'pending',
                'created_by' => $request->user_id ?? Auth::id(),
                'notes' => $request->notes,
            ]);

            // Xóa hết items cũ
            BatchItem::where('batch_id', $batch->id)->delete();

            // Tạo items mới từ request
            foreach ($request->batch_items as $item) {
                BatchItem::create([
                    'batch_id' => $batch->id,
                    'product_id' => $item['product_id'],
                    'received_quantity' => $item['received_quantity'],
                    'remaining_quantity' => 0,
                    'purchase_price' => $item['purchase_price'],
                    'total_amount' => $item['total_amount'],
                    'created_by' => Auth::id(),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.batches.show', $batch->id)
                ->with('success', 'Cập nhật đơn nhập hàng thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('admin.batches.edit', $batch->id)
                ->with('error', 'Đã xảy ra lỗi khi cập nhật đơn nhập hàng: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $batch = Batch::with(['batchItems.product'])->findOrFail($id);

                // Kiểm tra trạng thái hiện tại
                if ($batch->status !== 'draft') {
                    return back()->with('error', 'Chỉ có thể duyệt các đơn hàng ở trạng thái nháp.');
                }

                $user_id = Auth::id();
                $totalAmount = $batch->batchItems->sum('total_amount'); // Tổng tiền sau khi update
                $paidAmount = $batch->paid_amount ?? 0;

                if ($paidAmount >= $totalAmount) {
                    $paymentStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $paymentStatus = 'partially_paid';
                } else {
                    $paymentStatus = 'unpaid';
                }

                // Cập nhật trạng thái batch
                $batch->update([
                    'status' => 'completed',
                    'receipt_status' => 'completed',
                    'payment_status' => $paymentStatus,
                    'updated_by' => $user_id,
                    'updated_at' => now(),
                ]);

                // Xử lý từng batch item để cập nhật tồn kho
                foreach ($batch->batchItems as $batchItem) {
                    $product = $batchItem->product;

                    if (!$product) {
                        Log::warning("Product not found for batch item ID: {$batchItem->id}");
                        continue;
                    }

                    // Cập nhật current_quantity và inventory_status cho batch item
                    $batchItem->update([
                        'current_quantity' => $batchItem->received_quantity,
                        'inventory_status' => 'active',
                        'updated_by' => $user_id,
                    ]);

                    // Cập nhật tồn kho sản phẩm
                    $previousStock = $product->stock_quantity ?? 0;
                    $changeQty = $batchItem->received_quantity;
                    $newStock = $previousStock + $changeQty;

                    $product->update([
                        'stock_quantity' => $newStock,
                        'last_received_at' => now(),
                    ]);

                    // Ghi lại lịch sử biến động kho
                    InventoryTransaction::create([
                        'transaction_type_id' => 1, // ID cho loại giao dịch nhập kho
                        'product_id' => $product->id,
                        'quantity_change' => +$changeQty,
                        'unit_price' => $batchItem->purchase_price,
                        'total_value' => $batchItem->purchase_price * $changeQty,
                        'transaction_date' => now(),
                        'related_batch_id' => $batch->id,
                        'user_id' => $user_id,
                        'stock_after' => $newStock,
                        'note' => 'Duyệt đơn nhập hàng trực tiếp - Phiếu ' . $batch->batch_number
                    ]);

                    Log::info("Updated stock for product {$product->id}: {$previousStock} -> {$newStock} (+{$changeQty})");
                }

                Log::info("Direct import batch {$batch->id} approved successfully by user {$user_id}");

            });

        } catch (\Exception $e) {
            Log::error('Batch approval failed:', [
                'batch_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Có lỗi xảy ra khi duyệt đơn: ' . $e->getMessage());
        }
    }
    public function import(Request $request)
    {
        Log::info('Import request received.');

        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed during batch import.', ['errors' => $validator->errors()->toArray()]);
            return redirect()->back()->with([
                'importMessage' => 'Import thất bại. Vui lòng chọn một tệp Excel hợp lệ (xlsx, xls, csv).',
                'importStatus' => 'error'
            ]);
        }

        $excelFile = $request->file('excel_file');
        $user_id = Auth::id();

        if (is_null($user_id)) {
            Log::error('Import failed: User not authenticated. Auth::id() returned null.');
            return redirect()->back()->with([
                'importMessage' => 'Import thất bại. Vui lòng đăng nhập để thực hiện thao tác này.',
                'importStatus' => 'error'
            ]);
        }

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($excelFile->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = array_shift($rows);
            Log::info('Excel Headers (raw from file):', $headers);

            $mappedRows = [];
            foreach ($rows as $row) {
                if (empty(array_filter($row)))
                    continue;
                $mappedRow = [];
                foreach ($headers as $colKey => $headerName) {
                    $normalizedHeader = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', strtolower(trim($headerName))));
                    $mappedRow[$normalizedHeader] = $row[$colKey];
                }
                $mappedRows[] = $mappedRow;
            }

            Log::info('Mapped Excel Rows (first 5 for inspection):', array_slice($mappedRows, 0, 5));

            $batchesData = [];
            foreach ($mappedRows as $row) {
                $batchNumber = $row['m_phiu_nhp'] ?? null;
                if (!$batchNumber)
                    continue;
                $batchNumber = mb_strtolower(trim($batchNumber));

                if (!isset($batchesData[$batchNumber])) {
                    $batchesData[$batchNumber] = [
                        'batch' => [],
                        'items' => []
                    ];
                }

                if (empty($batchesData[$batchNumber]['batch'])) {
                    $mapPaymentStatus = fn($status) => match (mb_strtolower(trim($status))) {
                        'chưa thanh toán' => 'unpaid',
                        'đã thanh toán một phần' => 'partially_paid',
                        'đã thanh toán' => 'paid',
                        default => 'unpaid'
                    };

                    $mapReceiptStatus = fn($status) => match (mb_strtolower(trim($status))) {
                        'đã nhận một phần' => 'partially_received',
                        'đã nhận đủ' => 'completed',
                        'đã hủy' => 'cancelled',
                        default => 'partially_received'
                    };

                    $batchesData[$batchNumber]['batch'] = [
                        'batch_number' => $batchNumber,
                        'purchase_order_number' => $row['m_phiu_nhp'] ?? null,
                        'supplier_name' => $row['nh_cung_cp'] ?? null,
                        'received_date' => isset($row['ngy_nhn_hng']) && !empty($row['ngy_nhn_hng']) ? Carbon::createFromFormat('d/m/Y', $row['ngy_nhn_hng'])->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                        'invoice_number' => $row['s_ha_n'] ?? null,
                        'total_amount' => 0,
                        'payment_status' => $mapPaymentStatus($row['trng_thi_thanh_ton'] ?? ''),
                        'paid_amount' => (int) ($row['s_tin__thanh_ton'] ?? 0),
                        'receipt_status' => $mapReceiptStatus($row['trng_thi_nhn_hng'] ?? ''),
                        'notes' => $row['ghi_ch'] ?? null,
                        'created_by' => $user_id,
                        'updated_by' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }

                $productSku = $row['m_sku'] ?? null;
                if (!$productSku)
                    continue;

                $mapInventoryStatus = fn($status) => match (mb_strtolower(trim($status))) {
                    'hoạt động' => 'active',
                    'sắp hết hàng' => 'low_stock',
                    'hết hàng' => 'out_of_stock',
                    'hết hạn' => 'expired',
                    'hư hỏng' => 'damaged',
                    default => 'active'
                };

                $purchasePrice = (int) ($row['n_gi'] ?? 0);
                $orderedQuantity = (int) ($row['s_lng_t'] ?? 0);
                $receivedQuantity = (int) ($row['s_lng_nhn'] ?? 0);
                $rejectedQuantity = (int) ($row['s_lng_t_chi'] ?? 0);
                $currentQuantity = (int) ($row['s_lng_nhp'] ?? 0);

                $batchesData[$batchNumber]['items'][] = [
                    'product_sku' => $productSku,
                    'ordered_quantity' => $orderedQuantity,
                    'received_quantity' => $receivedQuantity,
                    'rejected_quantity' => $rejectedQuantity,
                    'remaining_quantity' => $orderedQuantity - $receivedQuantity - $rejectedQuantity,
                    'current_quantity' => $currentQuantity,
                    'purchase_price' => $purchasePrice,
                    // 'total_amount' => $purchasePrice * $receivedQuantity,
                    'total_amount' => $purchasePrice * ($receivedQuantity - $rejectedQuantity),
                    'manufacturing_date' => (isset($row['ngy_sn_xut']) && $row['ngy_sn_xut'] !== '—' && !empty($row['ngy_sn_xut'])) ? Carbon::createFromFormat('d/m/Y', $row['ngy_sn_xut'])->format('Y-m-d') : null,
                    'expiry_date' => (isset($row['ngy_ht_hn']) && $row['ngy_ht_hn'] !== '—' && !empty($row['ngy_ht_hn'])) ? Carbon::createFromFormat('d/m/Y', $row['ngy_ht_hn'])->format('Y-m-d') : null,
                    'inventory_status' => $mapInventoryStatus($row['trng_thi_tn_kho'] ?? ''),
                    'created_by' => $user_id,
                    'updated_by' => null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                $batchesData[$batchNumber]['batch']['total_amount'] += ($purchasePrice * $receivedQuantity);
            }

            foreach ($batchesData as $batchNumber => $data) {
                $batchData = $data['batch'];
                $batchItemsData = $data['items'];

                if ($batchData['supplier_name']) {
                    $supplier = Supplier::firstOrCreate(
                        ['name' => $batchData['supplier_name']],
                        ['created_by' => $user_id]
                    );
                    $batchData['supplier_id'] = $supplier->id;
                } else {
                    $batchData['supplier_id'] = null;
                }
                unset($batchData['supplier_name']);

                $purchaseOrder = null;
                if (!empty($batchData['purchase_order_number'])) {
                    $normalizedPoNumber = mb_strtolower(trim($batchData['purchase_order_number']));
                    $purchaseOrder = PurchaseOrder::where('po_number', $normalizedPoNumber)->first();
                    $batchData['purchase_order_id'] = $purchaseOrder?->id;
                } else {
                    $batchData['purchase_order_id'] = null;
                }
                unset($batchData['purchase_order_number']);

                if (is_null($batchData['purchase_order_id'])) {
                    unset($batchData['purchase_order_id']);
                }

                $existingBatch = Batch::where('batch_number', $batchData['batch_number'])->first();

                if ($existingBatch) {
                    $existingBatch->fill($batchData);
                    $existingBatch->updated_by = $user_id;
                    $existingBatch->save();
                    $batch = $existingBatch;
                } else {
                    $batch = Batch::create($batchData);
                }

                foreach ($batchItemsData as $itemData) {
                    $normalizedProductSku = mb_strtolower(trim($itemData['product_sku']));
                    $product = Product::where('sku', $normalizedProductSku)->first();
                    if (!$product)
                        continue;

                    $itemData['product_id'] = $product->id;
                    $itemData['batch_id'] = $batch->id;
                    $itemData['purchase_order_item_id'] = null;

                    if ($purchaseOrder) {
                        $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                            ->where('product_id', $product->id)
                            ->first();
                        if ($poItem) {
                            $itemData['purchase_order_item_id'] = $poItem->id;
                            $poItem->received_quantity += $itemData['received_quantity'];
                            $poItem->save();
                        }
                    }

                    unset($itemData['product_sku']);
                    BatchItem::create($itemData);
                }

                if ($purchaseOrder) {
                    $purchaseOrder->updateStatusBasedOnItems();
                }
            }

            DB::commit();
            return redirect()->back()->with([
                'importMessage' => 'Import thành công!',
                'importStatus' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi import batch: ' . $e->getMessage());
            return redirect()->back()->with([
                'importMessage' => 'Import thất bại. Vui lòng kiểm tra lại file Excel và dữ liệu. Lỗi: ' . $e->getMessage(),
                'importStatus' => 'error'
            ]);
        }
    }

    private function generateBatchNumber(): string
    {
        return DB::transaction(function () {
            $today = Carbon::now();
            $prefix = "REC-" . $today->format('Ymd');

            // Lấy số batch cuối cùng trong ngày hiện tại
            $lastBatch = Batch::where('batch_number', 'like', $prefix . '%')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$lastBatch) {
                return $prefix . '-001';
            }

            // Lấy số sequence hiện tại và tăng lên 1
            $currentSequence = (int) substr($lastBatch->batch_number, -3);
            $nextSequence = str_pad($currentSequence + 1, 3, '0', STR_PAD_LEFT);

            return $prefix . '-' . $nextSequence;
        }, 3); // Retry tối đa 3 lần nếu có conflict
    }

}
