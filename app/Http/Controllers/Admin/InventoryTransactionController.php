<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\BatchItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    public function index($id)
{
    $product = Product::findOrFail($id);
    $this->updateExpiredBatches($id);

    $transactions = InventoryTransaction::with([
        'transactionType' => function ($query) {
            $query->select('id', 'name', 'code');
        },
        'user' => function ($query) {
            $query->select('id', 'name');
        },
        'bill' => function ($query) {
            $query->select('id', 'bill_number');
        },
        'relatedPurchaseReturn' => function ($query) {
            $query->select('id', 'return_number');
        },
        'relatedBatch' => function ($query) use ($id) {
            $query->select('id', 'batch_number')
                  ->whereHas('batchItems', function ($q) use ($id) {
                      $q->where('product_id', $id);
                      // Bỏ điều kiện inventory_status != expired
                  });
        },
        'relatedBatch.batchItems' => function ($query) use ($id) {
            $query->select('id', 'batch_id', 'product_id', 'manufacturing_date', 'expiry_date', 'inventory_status')
                  ->where('product_id', $id);
        }
    ])
        ->where('product_id', $product->id)
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->paginate(10)
        ->withQueryString();

    Log::debug('Transactions for product ' . $id, $transactions->toArray());

    return Inertia::render('admin/products/InventoryHistory', [
        'product' => $product->only('id', 'name', 'sku'),
        'transactions' => $transactions->through(function ($transaction) {
            $batchItem = $transaction->relatedBatch && $transaction->relatedBatch->batchItems->isNotEmpty()
                ? $transaction->relatedBatch->batchItems->first()
                : null;

            $expiryStatus = null;
            if ($batchItem && $batchItem->expiry_date) {
                $expiryDate = Carbon::parse($batchItem->expiry_date);
                $today = Carbon::today();
                $daysUntilExpiry = $today->diffInDays($expiryDate, false);

                if ($daysUntilExpiry < 0) {
                    $expiryStatus = 'expired';
                } elseif ($daysUntilExpiry <= 30) {
                    $expiryStatus = 'near_expiry';
                } else {
                    $expiryStatus = 'valid';
                }
            }

            return [
                'id' => $transaction->id,
                'transaction_type' => $transaction->transactionType ? $transaction->transactionType->only('name', 'code') : null,
                'quantity_change' => $transaction->quantity_change,
                'stock_after' => $transaction->stock_after,
                'unit_price' => $transaction->unit_price,
                'total_value' => $transaction->total_value,
                'transaction_date' => $transaction->transaction_date,
                'related_bill' => $transaction->bill ? $transaction->bill->only('id', 'bill_number') : null,
                'related_purchase_return' => $transaction->relatedPurchaseReturn ? $transaction->relatedPurchaseReturn->only('id', 'return_number') : null,
                'related_batch' => $transaction->relatedBatch ? $transaction->relatedBatch->only('id', 'batch_number') : null,
                'user' => $transaction->user ? $transaction->user->only('id', 'name') : null,
                'note' => $transaction->note,
                'created_at' => $transaction->created_at,
                'manufacturing_date' => $batchItem ? $batchItem->manufacturing_date : null,
                'expiry_date' => $batchItem ? $batchItem->expiry_date : null,
                'expiry_status' => $expiryStatus,
            ];
        }),
    ]);
}

    private function updateExpiredBatches($productId)
    {
        $currentTime = Carbon::today('Asia/Ho_Chi_Minh');

        // Tìm các lô hàng hết hạn
        $expiredBatches = BatchItem::where('product_id', $productId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $currentTime)
            ->whereIn('inventory_status', ['active', 'low_stock'])
            ->where('current_quantity', '>', 0)
            ->whereHas('batch', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereIn('receipt_status', ['completed', 'partially_received']);
            })
            ->lockForUpdate()
            ->get();

        if ($expiredBatches->isEmpty()) {
            return;
        }

        // Cập nhật tồn kho và ghi giao dịch
        DB::transaction(function () use ($expiredBatches, $productId, $currentTime) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            // Lấy giao dịch gần nhất để lấy stock_after
            $latestTransaction = InventoryTransaction::where('product_id', $productId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            // Sử dụng stock_after từ giao dịch gần nhất, hoặc stock_quantity nếu không có giao dịch
            $currentStock = $latestTransaction ? $latestTransaction->stock_after : $product->stock_quantity;

            foreach ($expiredBatches as $batchItem) {
                $quantityToDeduct = $batchItem->current_quantity;

                // Đảm bảo không trừ quá số lượng hiện có
                if ($quantityToDeduct > $currentStock) {
                    $quantityToDeduct = $currentStock;
                }

                $newStock = $currentStock - $quantityToDeduct;

                // Cập nhật BatchItem
                $batchItem->update([
                    'current_quantity' => 0,
                    'inventory_status' => 'expired',
                    'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                ]);

                // Ghi giao dịch trong InventoryTransaction nếu có số lượng để trừ
                if ($quantityToDeduct > 0) {
                    InventoryTransaction::create([
                        'transaction_type_id' => 3, // Giả sử 3 là loại giao dịch cho hàng hết hạn
                        'product_id' => $batchItem->product_id,
                        'quantity_change' => -$quantityToDeduct,
                        'stock_after' => $newStock,
                        'unit_price' => $batchItem->purchase_price,
                        'total_value' => $quantityToDeduct * $batchItem->purchase_price,
                        'transaction_date' => Carbon::now('Asia/Ho_Chi_Minh'),
                        'related_batch_id' => $batchItem->batch_id,
                        'user_id' => auth()->id() ?? null,
                        'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                        'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                        'note' => 'Hàng hóa hết hạn được tự động cập nhật',
                    ]);
                }

                // Cập nhật số lượng tồn kho của sản phẩm
                $product->stock_quantity = $newStock;
                $product->save();

                // Cập nhật currentStock cho lô tiếp theo
                $currentStock = $newStock;
            }
        });
    }
}