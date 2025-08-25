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
        ->whereHas('batch', function ($query) {
            $query->whereNull('deleted_at')
                ->whereIn('receipt_status', ['completed', 'partially_received']);
        })
        ->lockForUpdate()
        ->get();

    if ($expiredBatches->isEmpty()) {
        return;
    }

    DB::transaction(function () use ($expiredBatches, $productId, $currentTime) {
        $product = Product::lockForUpdate()->findOrFail($productId);

        // Tính tổng stock_quantity từ tất cả các giao dịch trong inventory_transactions
        $totalStockChange = InventoryTransaction::where('product_id', $productId)
            ->sum('quantity_change');
        $currentStock = max(0, $totalStockChange); // Đảm bảo không âm

        // Tính số lượng cần trừ dựa trên received_quantity của các lô hết hạn
        $quantityToDeduct = $expiredBatches->sum('received_quantity');

        // Đảm bảo không trừ quá số lượng tồn kho hiện tại
        $quantityToDeduct = min($quantityToDeduct, $currentStock);
        $newStock = $currentStock - $quantityToDeduct;

        // Cập nhật trạng thái các lô hết hạn
        BatchItem::whereIn('id', $expiredBatches->pluck('id'))
            ->update([
                'inventory_status' => 'expired',
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

        // Ghi giao dịch trừ kho nếu có số lượng để trừ
        if ($quantityToDeduct > 0) {
            // Tính giá trị trung bình của các lô hết hạn
            $totalValue = $expiredBatches->sum(function ($batchItem) {
                return $batchItem->received_quantity * $batchItem->purchase_price;
            });
            $averageUnitPrice = $quantityToDeduct > 0 ? $totalValue / $quantityToDeduct : 0;

            InventoryTransaction::create([
                'transaction_type_id' => 3, // Giả sử 3 là loại giao dịch cho hàng hết hạn
                'product_id' => $productId,
                'quantity_change' => -$quantityToDeduct,
                'stock_after' => $newStock,
                'unit_price' => $averageUnitPrice,
                'total_value' => $totalValue,
                'transaction_date' => Carbon::now('Asia/Ho_Chi_Minh'),
                'related_batch_id' => null, // Có thể lưu danh sách batch_id nếu cần
                'user_id' => auth()->id() ?? 1, // Sử dụng user_id mặc định
                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'note' => 'Hàng hóa hết hạn được tự động cập nhật',
            ]);

            // Cập nhật stock_quantity trong bảng products
            $product->stock_quantity = $newStock;
            $product->save();

            // Ghi log để theo dõi
            Log::info('Expired batches processed for product ' . $productId, [
                'expired_batch_ids' => $expiredBatches->pluck('id')->toArray(),
                'quantity_deducted' => $quantityToDeduct,
                'new_stock' => $newStock,
            ]);

            // Cảnh báo nếu stock_quantity âm
            if ($newStock < 0) {
                Log::warning('Negative stock detected for product ' . $productId, [
                    'new_stock' => $newStock,
                    'expired_batch_ids' => $expiredBatches->pluck('id')->toArray(),
                ]);
            }
        }
    });
}
}