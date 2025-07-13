<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\BatchItem;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = BatchItem::with(['product.unit', 'batch']);

        if ($request->has('name') && $request->name !== null) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->has('unit') && $request->unit !== null) {
            $query->whereHas('product.unit', function ($q) use ($request) {
                $q->where('name', $request->unit);
            });
        }

        if ($request->has('min_price') && $request->min_price !== null) {
            $query->where('purchase_price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price !== null) {
            $query->where('purchase_price', '<=', $request->max_price);
        }

        if ($request->has('min_stock') && $request->min_stock !== null) {
            $query->where('current_quantity', '>=', $request->min_stock);
        }

        if ($request->has('max_stock') && $request->max_stock !== null) {
            $query->where('current_quantity', '<=', $request->max_stock);
        }

        if ($request->has('status') && in_array($request->status, ['in_stock', 'out_of_stock'])) {
            if ($request->status === 'in_stock') {
                $query->where('current_quantity', '>', 0);
            } elseif ($request->status === 'out_of_stock') {
                $query->where('current_quantity', '=', 0);
            }
        }

        $inventory = $query->get();

        $units = ProductUnit::pluck('name')->unique()->values()->toArray();

        return Inertia::render('admin/inventory/Index', [
            'inventory' => $inventory,
            'units' => $units,
        ]);
    }
    public function show($id)
    {
        $product = Product::with('unit')->findOrFail($id);

        $batchItems = BatchItem::where('product_id', $id)
            ->with([
                'batch' => function ($query) {
                    $query->select(
                        'id',
                        'batch_number',
                        'purchase_order_id',
                        'supplier_id',
                        'received_date',
                        'total_amount',
                        'payment_status',
                        'receipt_status',
                        'notes'
                    );
                },
            ])
            ->select(
                'id',
                'batch_id',
                'product_id',
                'ordered_quantity',
                'received_quantity',
                'current_quantity',
                'purchase_price',
                'total_amount',
                'inventory_status',
                'manufacturing_date',
                'expiry_date'
            )
            ->get();

        $totalInventory = $batchItems->sum('current_quantity');
        $lowStock = $batchItems->where('inventory_status', 'low_stock')->sum('current_quantity');
        $expired = $batchItems->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now())
            ->sum('current_quantity');

        $suppliers = Supplier::pluck('name', 'id')->toArray();

        return Inertia::render('admin/inventory/Show', [
            'product' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url ?? 'N/A',
                'description' => $product->description ?? 'Không có mô tả',
                'price' => $product->price ?? 0,
                'stock' => $product->stock ?? 0,
                'sku' => $product->sku ?? 'N/A',
                'status' => $product->status ?? 'N/A',
                'category' => is_string($product->category) ? json_decode($product->category, true) : $product->category ?? 'Không xác định',
                'unit' => $product->unit ? $product->unit->name : 'N/A',
            ],
            'batchItems' => $batchItems->map(function ($item) use ($suppliers) {
                return [
                    'id' => $item->id,
                    'batch_id' => $item->batch_id,
                    'batch_number' => $item->batch ? $item->batch->batch_number : 'N/A',
                    'purchase_order_id' => $item->batch ? $item->batch->purchase_order_id : null,
                    'supplier_id' => $item->batch ? $item->batch->supplier_id : null,
                    'supplier_name' => $item->batch && $item->batch->supplier_id ? ($suppliers[$item->batch->supplier_id] ?? 'Không xác định') : 'N/A',
                    'received_date' => $item->batch ? Carbon::parse($item->batch->received_date)->format('Y-m-d') : null,
                    'total_amount' => $item->batch ? $item->batch->total_amount : 0,
                    'payment_status' => $item->batch ? $item->batch->payment_status : 'N/A',
                    'receipt_status' => $item->batch ? $item->batch->receipt_status : 'N/A',
                    'notes' => $item->batch ? $item->batch->notes : null,
                    'ordered_quantity' => $item->ordered_quantity,
                    'received_quantity' => $item->received_quantity,
                    'current_quantity' => $item->current_quantity,
                    'purchase_price' => $item->purchase_price,
                    'total_item_amount' => $item->total_amount,
                    'inventory_status' => $item->inventory_status,
                    'manufacturing_date' => $item->manufacturing_date ? Carbon::parse($item->manufacturing_date)->format('Y-m-d') : null,
                    'expiry_date' => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : null,
                ];
            })->values(),
            'totalInventory' => $totalInventory,
            'lowStock' => $lowStock,
            'expired' => $expired,
            'suppliers' => $suppliers,
        ]);
    }
   public function update(Request $request, $id)
{
    $request->validate([
        'batchItems' => 'required|array',
        'batchItems.*.id' => 'required|exists:batch_items,id',
        'batchItems.*.current_quantity' => [
            'required',
            'integer',
            'min:0',
            function ($attribute, $value, $fail) use ($request) {
                $index = explode('.', $attribute)[1];
                $batchItemId = $request->batchItems[$index]['id'];
                $batchItem = BatchItem::find($batchItemId);
                if ($batchItem && $value > $batchItem->ordered_quantity) {
                    $fail("Số lượng hiện tại không được vượt quá số lượng đặt ({$batchItem->ordered_quantity}).");
                }
                if ($batchItem && $value > $batchItem->received_quantity) {
                    $fail("Số lượng hiện tại không được vượt quá số lượng nhập ({$batchItem->received_quantity}).");
                }
            },
        ],
    ]);

    $product = Product::findOrFail($id);
    $lowStockThreshold = $product->low_stock_threshold ?? 10;

    foreach ($request->batchItems as $itemData) {
        $batchItem = BatchItem::find($itemData['id']);

        if ($batchItem) {
            $batchItem->current_quantity = $itemData['current_quantity'];

            $newStatus = 'active';
            $expiryDate = $batchItem->expiry_date ? Carbon::parse($batchItem->expiry_date) : null;

            if ($expiryDate && $expiryDate->isPast()) {
                $newStatus = 'expired';
            } elseif ($batchItem->current_quantity <= 0) {
                $newStatus = 'out_of_stock';
            } elseif ($batchItem->current_quantity <= $lowStockThreshold) {
                $newStatus = 'low_stock';
            }

            $batchItem->inventory_status = $newStatus;
            $batchItem->save();
        }
    }

    $updatedBatchItems = BatchItem::where('product_id', $id)
        ->with([
            'batch' => function ($query) {
                $query->select(
                    'id',
                    'batch_number',
                    'purchase_order_id',
                    'supplier_id',
                    'received_date',
                    'total_amount',
                    'payment_status',
                    'receipt_status',
                    'notes'
                );
            },
        ])
        ->select(
            'id',
            'batch_id',
            'product_id',
            'ordered_quantity',
            'received_quantity',
            'current_quantity',
            'purchase_price',
            'total_amount',
            'inventory_status',
            'manufacturing_date',
            'expiry_date'
        )
        ->get();

    $totalInventory = $updatedBatchItems->sum('current_quantity');
    $lowStock = $updatedBatchItems->where('inventory_status', 'low_stock')->sum('current_quantity');
    $expired = $updatedBatchItems->whereNotNull('expiry_date')
        ->where('expiry_date', '<', Carbon::now())
        ->sum('current_quantity');

    $suppliers = Supplier::pluck('name', 'id')->toArray();

    return Inertia::render('admin/inventory/Show', [
        'product' => [
            'product_id' => $product->id,
            'name' => $product->name,
            'image_url' => $product->image_url ?? 'N/A',
            'description' => $product->description ?? 'Không có mô tả',
            'price' => $product->price ?? 0,
            'stock' => $product->stock ?? 0,
            'sku' => $product->sku ?? 'N/A',
            'status' => $product->status ?? 'N/A',
            'category' => is_string($product->category) ? json_decode($product->category, true) : $product->category ?? 'Không xác định',
            'unit' => $product->unit ? $product->unit->name : 'N/A',
        ],
        'batchItems' => $updatedBatchItems->map(function ($item) use ($suppliers) {
            return [
                'id' => $item->id,
                'batch_id' => $item->batch_id,
                'batch_number' => $item->batch ? $item->batch->batch_number : 'N/A',
                'purchase_order_id' => $item->batch ? $item->batch->purchase_order_id : null,
                'supplier_id' => $item->batch ? $item->batch->supplier_id : null,
                'supplier_name' => $item->batch && $item->batch->supplier_id ? ($suppliers[$item->batch->supplier_id] ?? 'Không xác định') : 'N/A',
                'received_date' => $item->batch ? Carbon::parse($item->batch->received_date)->format('Y-m-d') : null,
                'total_amount' => $item->batch ? $item->batch->total_amount : 0,
                'payment_status' => $item->batch ? $item->batch->payment_status : 'N/A',
                'receipt_status' => $item->batch ? $item->batch->receipt_status : 'N/A',
                'notes' => $item->batch ? $item->batch->notes : null,
                'ordered_quantity' => $item->ordered_quantity,
                'received_quantity' => $item->received_quantity,
                'current_quantity' => $item->current_quantity,
                'purchase_price' => $item->purchase_price,
                'total_item_amount' => $item->total_amount,
                'inventory_status' => $item->inventory_status,
                'manufacturing_date' => $item->manufacturing_date ? Carbon::parse($item->manufacturing_date)->format('Y-m-d') : null,
                'expiry_date' => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : null,
            ];
        })->values(),
        'totalInventory' => $totalInventory,
        'lowStock' => $lowStock,
        'expired' => $expired,
        'suppliers' => $suppliers,
    ]);
}
}
