<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\purchaseReturn;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $purchaseReturns = PurchaseReturn::with(['supplier:id,name', 'createdBy:id,name'])
            ->orderByDesc('id') // ← sắp xếp theo ID giảm dần
            ->get();

        return Inertia::render('admin/purchaseReturn/Index', [
            'purchaseReturns' => $purchaseReturns->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'supplier_name' => $return->supplier->name ?? 'Không xác định',
                    'return_date' => Carbon::parse($return->return_date)->format('d/m/Y'),
                    'status' => $return->status,
                    'total_value_returned' => number_format($return->total_value_returned),
                    'created_by' => $return->createdBy->name ?? 'Không xác định',
                ];
            }),
        ]);
    }

    public function show($id)
    {
        $purchaseReturn = PurchaseReturn::with([
            'supplier:id,name',
            'createdBy:id,name',
            'items' => function ($query) {
                $query->with('product:id,name,sku');
            }
        ])->findOrFail($id);

        return Inertia::render('admin/purchaseReturn/Show', [
            'purchaseReturn' => [
                'id' => $purchaseReturn->id,
                'return_number' => $purchaseReturn->return_number,
                'return_date' => $purchaseReturn->return_date,
                'status' => $purchaseReturn->status,
                'reason' => $purchaseReturn->reason,
                'total_items_returned' => $purchaseReturn->total_items_returned,
                'total_value_returned' => $purchaseReturn->total_value_returned,
                'supplier_name' => $purchaseReturn->supplier->name ?? '',
                'created_by' => $purchaseReturn->createdBy->name ?? '',
                'items' => $purchaseReturn->items->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'batch_number' => $item->batch_number,
                        'manufacturing_date' => $item->manufacturing_date,
                        'expiry_date' => $item->expiry_date,
                        'unit_cost' => $item->unit_cost,
                        'quantity_returned' => $item->quantity_returned,
                        'subtotal' => $item->subtotal,
                        'reason' => $item->reason,
                    ];
                }),
            ]
        ]);
    }
    public function edit($id)
    {
        $purchaseReturn = PurchaseReturn::with([
            'supplier:id,name,email,phone,address',
            'items.product:id,name,sku',
            'items.batch:id,batch_number', // 👈 load batch nếu có liên kết
            'purchaseOrder:id,po_number',
            'createdBy:id,name',
        ])->findOrFail($id);

        $firstItem = $purchaseReturn->items->first();

        return Inertia::render('admin/purchaseReturn/Edit', [
            'purchaseReturn' => [
                'id' => $purchaseReturn->id,
                'return_number' => $purchaseReturn->return_number,
                'return_date' => $purchaseReturn->return_date,
                'status' => $purchaseReturn->status,
                'reason' => $purchaseReturn->reason,
                'total_items_returned' => $purchaseReturn->total_items_returned,
                'total_value_returned' => $purchaseReturn->total_value_returned,
                'supplier_id' => $purchaseReturn->supplier_id,
                'supplier_name' => $purchaseReturn->supplier->name ?? '',
                'supplier_email' => $purchaseReturn->supplier->email ?? '',
                'supplier_phone' => $purchaseReturn->supplier->phone ?? '',
                'supplier_address' => $purchaseReturn->supplier->address ?? '',
                'purchase_order_id' => $purchaseReturn->purchase_order_id,
                'batch_number' => $firstItem->batch->batch_number ?? $firstItem->batch_number ?? '',
                'batch_id' => $firstItem->batch->id ?? null,
                'created_by' => $purchaseReturn->createdBy->name ?? '',
                'items' => $purchaseReturn->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? '',
                        'product_sku' => $item->product->sku ?? '',
                        'batch_number' => $item->batch->batch_number ?? $item->batch_number ?? '',
                        'batch_id' => $item->batch->id ?? null,
                        'manufacturing_date' => $item->manufacturing_date ? Carbon::parse($item->manufacturing_date)->format('Y-m-d') : null,
                        'expiry_date' => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : null,
                        'unit_cost' => $item->unit_cost,
                        'quantity_returned' => $item->quantity_returned,
                        'subtotal' => $item->subtotal,
                        'reason' => $item->reason,
                    ];
                }),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {

        $purchaseReturn = PurchaseReturn::findOrFail($id);

        $purchaseReturn->update([
            'reason' => $request->input('reason'), // ✅ sửa lại cho đúng cột trong DB
        ]);

        return redirect()->route('admin.purchaseReturn.edit', $purchaseReturn->id)
            ->with('success', 'Cập nhật lý do trả hàng thành công.');
    }
    public function create(Request $request)
    {
        $batchId = $request->query('batch_id');
        $purchaseOrderData = null;
        $error = null;

        if ($batchId) {
            $batch = Batch::with([
                'batchItems' => function ($query) {
                    $query->with([
                        'product:id,name,sku,image_url',
                        'purchaseOrderItem:id,ordered_quantity,unit_cost',
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
                            'inventory_status'
                        );
                },
                'purchaseOrder:id,po_number',
                'supplier:id,name,email,phone,address',
            ])->select(
                    'id',
                    'batch_number',
                    'purchase_order_id',
                    'supplier_id',
                    'received_date',
                    'invoice_number',
                    'total_amount'
                )->find($batchId);

            if (!$batch) {
                $error = 'Batch not found or invalid.';
            } else {
                $purchaseOrderData = [
                    // Nếu không có purchaseOrder thì lấy luôn purchase_order_id từ batch
                    'id' => $batch->purchaseOrder->id ?? $batch->purchase_order_id ?? null,
                    'supplier_id' => $batch->supplier->id ?? null,
                    'code' => $batch->purchaseOrder->po_number ?? 'N/A',
                    'batch_number' => $batch->batch_number, // Add batch_number
                    'supplier_name' => $batch->supplier->name ?? 'N/A',
                    'supplier_email' => $batch->supplier->email ?? null,
                    'supplier_phone' => $batch->supplier->phone ?? null,
                    'supplier_address' => $batch->supplier->address ?? null,
                    'supplier_avatar_url' => null, // Set to null as per previous fix
                    'items' => $batch->batchItems->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'batch_id' => $item->batch_id ?? null,
                            'purchase_order_item_id' => $item->purchase_order_item_id ?? null,
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'product_sku' => $item->product->sku ?? 'N/A',
                            'quantity_received' => $item->current_quantity,
                            'unit_cost' => $item->purchase_price,
                            'product_image_url' => $item->product->image_url ?? null,
                            'batch_number' => $item->batch->batch_number ?? null, // Thêm dòng này
                            'manufacturing_date' => $item->manufacturing_date ?? null, // Thêm dòng này
                            'expiry_date' => $item->expiry_date ?? null, // Thêm dòng này
                        ];
                    })->toArray(),
                ];
            }
        }

        return Inertia::render('admin/purchaseReturn/Create', [
            'purchaseOrder' => $purchaseOrderData,
            'currentLocationName' => 'Cửa hàng chính',
            'error' => $error,
        ]);
    }
 public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'return_date' => 'required|date',
                'reason' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.batch_id' => 'nullable|exists:batches,id',
                'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
                'items.*.quantity_returned' => 'required|integer|min:1',
                'items.*.unit_cost' => 'required|numeric|min:0',
                'items.*.product_name' => 'nullable|string|max:255',
                'items.*.product_sku' => 'nullable|string|max:255',
                'items.*.batch_number' => 'nullable|string|max:255',
                'items.*.manufacturing_date' => 'nullable|date',
                'items.*.expiry_date' => 'nullable|date',
                'items.*.reason' => 'nullable|string|max:255',
            ]);

            // Tính toán tổng giá trị trả hàng
            $totalValueReturned = collect($validatedData['items'])->sum(function ($item) {
                return $item['quantity_returned'] * $item['unit_cost'];
            });

            // Tạo PurchaseReturn
            $purchaseReturn = PurchaseReturn::create([
                'return_number' => 'TRA-' . now()->format('Ymd-His') . '-' . rand(100, 999),
                'supplier_id' => $validatedData['supplier_id'],
                'purchase_order_id' => $validatedData['purchase_order_id'],
                'status' => 'pending',
                'return_date' => Carbon::parse($validatedData['return_date']),
                'reason' => $validatedData['reason'],
                'total_items_returned' => count($validatedData['items']),
                'total_value_returned' => $totalValueReturned,
                'created_by' => auth()->id(),
            ]);

            // Tạo PurchaseReturnItems và cập nhật số lượng BatchItem
            foreach ($validatedData['items'] as $item) {
                $batch = null;
                $batchItem = null;

                if (!empty($item['batch_id'])) {
                    $batch = Batch::find($item['batch_id']);
                    $batchItem = BatchItem::where('batch_id', $item['batch_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();
                }

                $manufacturingDateToSave = $batchItem->manufacturing_date ?? ($item['manufacturing_date'] ?? null);
                $expiryDateToSave = $batchItem->expiry_date ?? ($item['expiry_date'] ?? null);
                $itemReasonToSave = $item['reason'] ?? null;
                $batchNumberToSave = $batch->batch_number ?? ($item['batch_number'] ?? null);
                $productNameToSave = $item['product_name'] ?? '';
                $productSkuToSave = $item['product_sku'] ?? '';

                $purchaseReturn->items()->create([
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                    'product_name' => $productNameToSave,
                    'product_sku' => $productSkuToSave,
                    'batch_number' => $batchNumberToSave,
                    'manufacturing_date' => $manufacturingDateToSave,
                    'expiry_date' => $expiryDateToSave,
                    'quantity_returned' => $item['quantity_returned'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $item['quantity_returned'] * $item['unit_cost'],
                    'reason' => $itemReasonToSave,
                ]);

                // Trừ số lượng trong batch_items
                if ($batchItem) {
                    $batchItem->current_quantity -= $item['quantity_returned'];
                    $batchItem->received_quantity -= $item['quantity_returned'];
                    $batchItem->save();
                }
            }

            DB::commit();

            return redirect()->route('admin.purchaseReturn.index')
                ->with('success', 'Phiếu trả hàng đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo phiếu trả hàng: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Tạo phiếu trả hàng thất bại. Vui lòng thử lại.');
        }
    }

    public function complete(PurchaseReturn $purchaseReturn)
    {
        // Ensure the purchase return can be completed
        if ($purchaseReturn->status !== 'pending') {
            return back()->with('error', 'Phiếu trả hàng chỉ có thể hoàn thành khi ở trạng thái Chờ duyệt.');
        }

        // Update the status to completed
        $purchaseReturn->status = 'completed';
        $purchaseReturn->save();

        return back()->with('success', 'Đã hoàn thành phiếu trả hàng thành công!');
    }
}
