<?php

namespace Database\Seeders;

use App\Models\PurchaseReturn;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseReturnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchase_returns = [
            // Trường hợp trả hàng cho PO-20240601-002 (purchase_order_id: 2)
            // Item: Bình đun siêu tốc 1.8L (product_id: 5), nhận 9/10 cái -> trả 1 cái lỗi
            [
                'return_number' => 'PTR-20250608-001',
                'purchase_order_id' => 2, // PO-20240601-002
                'supplier_id' => 2, // Supplier của PO-20240601-002
                'status' => 'completed', // Đã cập nhật theo ENUM mới
                'return_date' => '2025-06-09',
                'reason' => 'Sản phẩm lỗi, không hoạt động',
                'total_items_returned' => 1,
                'total_value_returned' => 180000, // 1 * unit_cost của Bình đun siêu tốc
                'created_by' => 1, // User tạo phiếu trả hàng
                'created_at' => '2025-06-09 10:00:00',
                'updated_at' => '2025-06-09 10:00:00',
            ],
            // Trường hợp trả hàng cho PO-20240606-007 (purchase_order_id: 7)
            // Item: Mì ly hảo hạng 65g (product_id: 1), nhận thiếu 2 cái -> trả vì thiếu hàng
            [
                'return_number' => 'PTR-20250615-002',
                'purchase_order_id' => 7, // PO-20240606-007
                'supplier_id' => 6, // Supplier của PO-20240606-007
                'status' => 'pending', // Đã cập nhật theo ENUM mới
                'return_date' => '2025-06-16',
                'reason' => 'Thiếu hàng so với đơn đặt hàng',
                'total_items_returned' => 2,
                'total_value_returned' => 10000, // 2 * unit_cost của Mì ly
                'created_by' => 1,
                'created_at' => '2025-06-16 09:00:00',
                'updated_at' => '2025-06-16 09:00:00',
            ],
            // Thêm một số bản ghi trả hàng khác để đa dạng dữ liệu
            [
                'return_number' => 'PTR-20250620-003',
                'purchase_order_id' => 4, // PO-20240603-004
                'supplier_id' => 4,
                'status' => 'approved', // Đã cập nhật theo ENUM mới
                'return_date' => '2025-06-20',
                'reason' => 'Sản phẩm bị hư hỏng trong quá trình vận chuyển',
                'total_items_returned' => 5, // Ví dụ: 5 bánh quy
                'total_value_returned' => 100000, // 5 * unit_cost của Bánh quy bơ
                'created_by' => 1,
                'created_at' => '2025-06-20 11:00:00',
                'updated_at' => '2025-06-20 11:00:00',
            ],
            [
                'return_number' => 'PTR-20250625-004',
                'purchase_order_id' => 12, // PO-20240611-012
                'supplier_id' => 1,
                'status' => 'rejected', // Đã cập nhật theo ENUM mới (ví dụ)
                'return_date' => '2025-06-25',
                'reason' => 'Hàng không đúng mẫu mã',
                'total_items_returned' => 3, // Ví dụ: 3 bánh quy
                'total_value_returned' => 60000, // 3 * unit_cost của Bánh quy bơ
                'created_by' => 1,
                'created_at' => '2025-06-25 14:00:00',
                'updated_at' => '2025-06-25 14:00:00',
            ],
        ];

        foreach ($purchase_returns as $purchase_return) {
            PurchaseReturn::create($purchase_return);
        }
    }
}
