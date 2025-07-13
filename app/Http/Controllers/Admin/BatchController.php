<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Session;
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
use PhpOffice\PhpSpreadsheet\IOFactory;

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

            $receipt_status = 'partially_received';
            if (!$hasRejected && $totalReceived >= $totalOrdered) {
                $receipt_status = 'completed';
            } elseif ($totalOrdered > 0 && $totalReceived == 0 && $hasRejected) {
                $receipt_status = 'cancelled';
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
                    $remainingQty = $orderedQty - $receivedQty - $rejectedQty;
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

                $poItem->updateReceivedQuantity();
            }

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

    public function create()
    {
        return Inertia::render('admin/batches/Create');
    }

    public function store(Request $request)
    {
        return $this->save($request);
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
}
