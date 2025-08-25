<?php

namespace App\Http\Controllers\Admin;

use App\Models\BatchItem;
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
use Illuminate\Support\Facades\DB;

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

    public function syncInventory()
    {
        try {
            return DB::transaction(function () {
                $updatedCount = 0;
                $products = Product::where('is_active', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'stock_quantity')
                    ->get();

                foreach ($products as $product) {
                    $query = BatchItem::where('product_id', $product->id)
                        ->whereIn('inventory_status', ['active', 'expiring_soon'])
                        ->where('current_quantity', '>', 0)
                        ->whereHas('batch', function ($q) {
                            $q->whereNull('deleted_at')
                                ->where('receipt_status', 'completed');
                        })
                        ->where(function ($q) {
                            $q->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                        });

                    $totalBatchQuantity = $query->sum('current_quantity');

                    if ($product->stock_quantity != $totalBatchQuantity) {
                        $product->stock_quantity = $totalBatchQuantity;
                        $product->save();
                        $updatedCount++;
                    }
                }

                return redirect()->route('admin.inventory.index')->with('success', 'Đồng bộ tồn kho hoàn tất với ' . $updatedCount . ' sản phẩm được update!');

                // return response()->json([
                //     'message' => 'Đồng bộ tồn kho hoàn tất!',
                //     'updated_products' => $updatedCount
                // ], 200);
            });
        } catch (\Exception $e) {
            // return response()->json([
            //     'errors' => ['server' => 'Có lỗi khi đồng bộ tồn kho.']
            // ], 500);
            return redirect()->route('admin.inventory.index')->with('error', 'Có lỗi khi đồng bộ tồn kho.');
        }
    }
}
