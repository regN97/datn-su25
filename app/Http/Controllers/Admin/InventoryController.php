<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\ProductSupplier;
use App\Models\ProductUnit;
use App\Models\Supplier;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'unit', 'suppliers', 'batchItems'])->get();
        $categories = Category::all();
        $units = ProductUnit::all();
        $suppliers = ProductSupplier::all();
        $all_suppliers = Supplier::all();
        $availableProducts = [];

        foreach ($products as $product) {
            $totalQuantity = $product->getCurrentStock();

            if ($totalQuantity > 0) {
                $availableProducts[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'value' => $totalQuantity,
                ];
            }
        }

        return Inertia::render('admin/inventory/Index')->with([
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
            'allSuppliers' => $all_suppliers,
            'availableProducts' => $availableProducts,
        ]);
    }
    public function show($id)
    {
        $product = Product::with(['category', 'unit', 'suppliers'])->find($id);

        $availableProducts = [];
        $totalQuantity = $product->getCurrentStock();
        if ($totalQuantity > 0) {
            $availableProducts[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'value' => $totalQuantity,
            ];
        }

        return Inertia::render('admin/inventory/Show')->with([
            'product' => $product,
            'unit' => $product->unit,
            'category' => $product->category,
            'batch' => $product->batches,
            'batchItems' => $product->batchItems,
            'availableProducts' => $availableProducts,
        ]);
    }
    public function update(Request $request, $id) {}
}
