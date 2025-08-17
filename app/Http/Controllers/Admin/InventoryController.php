<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\ProductSupplier;
use App\Models\ProductUnit;
use App\Models\Supplier;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'unit', 'suppliers'])->get();
        $categories = Category::all();
        $units = ProductUnit::all();
        $suppliers = ProductSupplier::all();
        $all_suppliers = Supplier::all();
        return Inertia::render('admin/inventory/Index')->with([
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
            'allSuppliers' => $all_suppliers,
        ]);
    }
    public function show($id)
    {

    }
    public function update(Request $request, $id)
    {

    }
}