<?php

namespace App\Http\Controllers\Cashier;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\BatchItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class POSController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->select('id', 'name', 'sku', 'barcode', 'selling_price', 'image_url', 'category_id')
            ->where('is_active', 1)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->selling_price,
                    'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png', 
                    'category' => $product->category ? $product->category->name : 'Không có danh mục',
                    'stock' => $this->getProductStock($product->id), 
                ];
            });

        // dd($products); 

        return Inertia::render('cashier/POS', [
            'products' => $products,
        ]);
    }

    private function getProductStock($productId)
    {
        return BatchItem::where('product_id', $productId)
            ->where('inventory_status', 'active')
            ->sum('current_quantity');
    }
}