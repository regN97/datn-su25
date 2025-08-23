<?php
namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class InventoryTransactionController extends Controller
{
    public function index($id)
    {
        $product = Product::findOrFail($id);

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
            'relatedBatch' => function ($query) {
                $query->select('id', 'batch_number');
            }
        ])
            ->where('product_id', $product->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc') // Thêm để xử lý giao dịch cùng thời điểm
            ->paginate(10)
            ->withQueryString();

        Log::debug('Transactions for product ' . $id, $transactions->toArray());

        return Inertia::render('admin/products/InventoryHistory', [
            'product' => $product->only('id', 'name', 'sku'),
            'transactions' => $transactions->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_type' => $transaction->transactionType ? $transaction->transactionType->only('name', 'code') : null,
                    'quantity_change' => $transaction->quantity_change,
                    'stock_after' => $transaction->stock_after,
                    'unit_price' => $transaction->unit_price,
                    'total_value' => $transaction->total_value,
                    'transaction_date' => $transaction->transaction_date,
                    'related_bill' => $transaction->relatedBill ? $transaction->relatedBill->only('id', 'bill_number') : null,
                    'related_purchase_return' => $transaction->relatedPurchaseReturn ? $transaction->relatedPurchaseReturn->only('id', 'return_number') : null,
                    'related_batch' => $transaction->relatedBatch ? $transaction->relatedBatch->only('id', 'batch_number') : null,
                    'user' => $transaction->user ? $transaction->user->only('id', 'name') : null,
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at,
                ];
            }),
        ]);
    }
}