<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;

class InventoryController extends Controller
{
    public function index($id)
    {
        $product = Product::findOrFail($id);
        $transactions = InventoryTransaction::with('transactionType', 'user')
            ->where('product_id', $product->id)
            ->orderBy('transaction_date', 'desc')
            ->paginate(10)
            ->withQueryString();
        ;

        return Inertia::render('admin/products/InventoryHistory', [
            'product' => $product,
            'transactions' => $transactions,
        ]);
    }
    public function show($id)
    {
        $product = Product::with(['category', 'unit', 'suppliers'])->find($id);
        return Inertia::render('admin/inventory/Show')->with([
            'product' => $product,
            'unit' => $product->unit,
            'category' => $product->category,
            'batch' => $product->batches,
            'batchItems' => $product->batchItems
        ]);
    }
    public function update(Request $request, $id)
    {

    }
}
