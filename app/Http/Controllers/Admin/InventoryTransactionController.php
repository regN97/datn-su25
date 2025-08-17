<?php
namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
class InventoryTransactionController extends Controller
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
}