<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
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
        $batches = ProductBatch::with('supplier')->get();

        return Inertia::render('admin/batches/Index', [
            'batches' => $batches,
        ]);
    }
    public function show($id)
    {
        $batch = ProductBatch::with(['product', 'supplier'])->findOrFail($id);

        return Inertia::render('admin/batches/Show', [
            'batch' => $batch,
        ]);
        
    }
}
