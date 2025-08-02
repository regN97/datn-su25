<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\BatchItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncStock extends Command
{
    protected $signature = 'stock:sync';
    protected $description = 'Đồng bộ hóa stock_quantity của sản phẩm với số lượng batch_items';

    public function handle()
    {
        try {
            DB::transaction(function () {
                $products = Product::where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->select('id', 'stock_quantity')
                    ->get();

                foreach ($products as $product) {
                    $totalBatchQuantity = BatchItem::where('product_id', $product->id)
                        ->where('inventory_status', 'active')
                        ->where('current_quantity', '>', 0)
                        ->whereHas('batch', function ($query) {
                            $query->whereNull('deleted_at')
                                ->where('receipt_status', 'completed');
                        })
                        ->where(function ($query) {
                            $query->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                        })
                        ->sum('current_quantity');

                    if ($product->stock_quantity != $totalBatchQuantity) {
                        Log::warning('Phát hiện không đồng bộ tồn kho trong quá trình đồng bộ', [
                            'product_id' => $product->id,
                            'stock_quantity' => $product->stock_quantity,
                            'batch_quantity' => $totalBatchQuantity,
                        ]);

                        $product->stock_quantity = $totalBatchQuantity;
                        $product->save();

                        Log::info('Đã đồng bộ tồn kho', [
                            'product_id' => $product->id,
                            'new_stock_quantity' => $totalBatchQuantity,
                        ]);
                    }
                }

                $this->info('Đồng bộ hóa tồn kho hoàn tất thành công.');
            });
        } catch (\Exception $e) {
            Log::error('Lỗi trong quá trình đồng bộ hóa tồn kho', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Không thể đồng bộ hóa tồn kho.');
        }
    }
}