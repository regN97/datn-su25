<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ProductBatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with('supplier')->get();

        return Inertia::render('admin/batches/Index', [
            'batches' => $batches,
        ]);
    }
    public function show($id)
    {
        $batch = Batch::with([
            'supplier',
            'products' => function ($query) {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.image_url',
                    'product_units.name as unit_name'
                )
                    ->join('product_units', 'products.unit_id', '=', 'product_units.id') 
                    ->withPivot('purchase_price', 'initial_quantity', 'current_quantity');
            }
        ])->findOrFail($id);

        $batchData = $batch->toArray();

        $batchData['products_in_batch'] = $batch->products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url,
                'unit' => $product->unit_name,
                'purchase_price' => $product->pivot->purchase_price,
                'initial_quantity' => $product->pivot->initial_quantity,
                'current_quantity' => $product->pivot->current_quantity,
            ];
        })->all();
        unset($batchData['products']);

        return Inertia::render('admin/batches/Show', [
            'batch' => $batchData,
        ]);
    }
}
