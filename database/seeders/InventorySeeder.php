<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\ProductBatch; // Import ProductBatch để lấy dữ liệu
use Carbon\Carbon;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Lấy tất cả các product_batches để tạo bản ghi kho
        // Đảm bảo ProductBatchSeeder đã chạy trước Seeder này
        $productBatches = ProductBatch::all();

        foreach ($productBatches as $productBatch) {
            $stockStatus = 'in_stock';

            // Xác định trạng thái tồn kho dựa trên số lượng hiện tại
            if ($productBatch->current_quantity <= 0) {
                $stockStatus = 'out_of_stock';
            } elseif ($productBatch->current_quantity < ($productBatch->initial_quantity * 0.2)) {
                $stockStatus = 'low_stock';
            }

            Inventory::create([
                'product_id' => $productBatch->product_id,
                'product_batch_id' => $productBatch->id, // Đổi tên trường từ batch_id thành product_batch_id
                'quantity' => $productBatch->current_quantity,
                'stock_status' => $stockStatus,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
