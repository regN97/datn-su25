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
        // Lưu ý: Không có lệnh truncate() ở đây.
        // Nếu bạn chạy seeder nhiều lần mà không làm sạch DB, sẽ có lỗi trùng lặp
        // nếu bạn cố gắng chèn cùng một product_id và batch_id nhiều lần
        // (ví dụ: nếu bạn đặt unique trên cặp này).

        // Lấy tất cả các lô sản phẩm đã có để tạo bản ghi kho
        // Đảm bảo ProductBatchSeeder đã chạy trước Seeder này
        $productBatches = ProductBatch::all();

        foreach ($productBatches as $batch) {
            $stockStatus = 'in_stock';
            // Xác định trạng thái tồn kho dựa trên số lượng hiện tại
            if ($batch->current_quantity <= 0) {
                $stockStatus = 'out_of_stock';
            } elseif ($batch->current_quantity < ($batch->initial_quantity * 0.2)) { // Ví dụ: dưới 20% là low_stock
                $stockStatus = 'low_stock';
            }

            Inventory::create([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id, // Sử dụng ID thực tế của lô sản phẩm
                'quantity' => $batch->current_quantity,
                'stock_status' => $stockStatus,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
