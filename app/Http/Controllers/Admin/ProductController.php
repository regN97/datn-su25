<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;

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


    public function create()
    {
        return Inertia::render('admin/products/Create', [
            'suppliers' => Supplier::all(['id', 'name']),
            'categories' => Category::with('children')->whereNull('parent_id')->get(['id', 'name', 'parent_id']),
            'product_units' => ProductUnit::all(['id', 'name']),
            'csrf_token' => csrf_token(),
        ]);
    }

     public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $uploadedFilePath = null; 
            if ($request->input('image_input_type') === 'file' && $request->hasFile('image_file')) {
                $uploadedFilePath = $request->file('image_file')->store('product_images', 'public');
                $data['image_url'] = Storage::url($uploadedFilePath);
                unset($data['image_file']);
            } elseif ($request->input('image_input_type') === 'url' && !empty($data['image_url'])) {
                unset($data['image_file']);
            } else {
                $data['image_url'] = null; 
                unset($data['image_file']);
            }

            $selectedSupplierIds = $data['selected_supplier_ids'];
            unset($data['selected_supplier_ids']); 

            $product = Product::create($data);

            // Đồng bộ nhà cung cấp sau khi tạo sản phẩm
            $product->suppliers()->sync($selectedSupplierIds);

            return redirect()->route('admin.products.index')->with('success', 'Sản phẩm đã được tạo thành công.');
        
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
        //
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
