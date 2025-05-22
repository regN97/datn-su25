<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        $categories = Category::all();
        $units = ProductUnit::all();
        return Inertia::render('admin/products/Index')->with([
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //fix cứng data
return Inertia::render('admin/products/Edit', [
    'product' => $product = [
        'id' => $id,
        'name' => 'Product ' . $id,
        'price' => 100 * $id,
    ],
    'categories' => $categorie = [
        ['id' => 1, 'name' => 'Category 1'],
        ['id' => 2, 'name' => 'Category 2'],
        ['id' => 3, 'name' => 'Category 3'],
    ],
    'suppliers' => $suppliers = [
        ['id' => 1, 'name' => 'Supplier 1'],
        ['id' => 2, 'name' => 'Supplier 2'],
        ['id' => 3, 'name' => 'Supplier 3'],
    ],
    'productSuppliers' => $productSuppliers = [
        ['id' => 1, 'name' => 'Supplier 1'],
        ['id' => 2, 'name' => 'Supplier 2'],
    ],

]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
