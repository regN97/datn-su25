<?php

namespace Database\Seeders;

use App\Models\PurchaseReturnItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseReturnItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchase_return_items = [
            // Return 1: PTR-20250608-001 (purchase_return_id: 1)
            // Trả 1 Bình đun siêu tốc (product_id: 5) từ PO-20240601-002 (purchase_order_item_id: 3)
            [
                'purchase_return_id' => 1,
                'purchase_order_item_id' => 3, // Item cho Bình đun siêu tốc trong PO-20240601-002
                'product_id' => 5,
                'batch_number' => 'BDST-202505-001',
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => null,
                'product_name' => 'Bình đun siêu tốc 1.8L',
                'product_sku' => 'G7-BDST-015',
                'quantity_returned' => 1,
                'unit_cost' => 180000,
                'subtotal' => 180000, // 1 * 180000
                'reason' => 'Sản phẩm lỗi, không hoạt động',
                'created_at' => '2025-06-09 10:00:00',
                'updated_at' => '2025-06-09 10:00:00',
            ],

            // Return 2: PTR-20250615-002 (purchase_return_id: 2)
            // Trả 2 Mì ly hảo hạng (product_id: 1) từ PO-20240606-007 (purchase_order_item_id: 11)
            [
                'purchase_return_id' => 2,
                'purchase_order_item_id' => 11, // Item cho Mì ly trong PO-20240606-007
                'product_id' => 1,
                'batch_number' => 'MLH-202505-005',
                'manufacturing_date' => '2025-05-20',
                'expiry_date' => '2025-11-20',
                'product_name' => 'Mì ly hảo hạng 65g',
                'product_sku' => 'G7-MLH-011',
                'quantity_returned' => 2,
                'unit_cost' => 5000,
                'subtotal' => 10000, // 2 * 5000
                'reason' => 'Thiếu hàng so với đơn đặt hàng',
                'created_at' => '2025-06-16 09:00:00',
                'updated_at' => '2025-06-16 09:00:00',
            ],

            // Return 3: PTR-20250620-003 (purchase_return_id: 3)
            // Trả 5 Bánh quy bơ (product_id: 2) từ PO-20240603-004 (purchase_order_item_id: 6)
            [
                'purchase_return_id' => 3,
                'purchase_order_item_id' => 6, // Item cho Bánh quy bơ trong PO-20240603-004
                'product_id' => 2,
                'batch_number' => 'BQB-202505-006',
                'manufacturing_date' => '2025-05-10',
                'expiry_date' => '2026-05-10',
                'product_name' => 'Bánh quy bơ hộp 200g',
                'product_sku' => 'G7-BQB-012',
                'quantity_returned' => 5,
                'unit_cost' => 20000,
                'subtotal' => 100000, // 5 * 20000
                'reason' => 'Sản phẩm bị hư hỏng trong quá trình vận chuyển',
                'created_at' => '2025-06-20 11:00:00',
                'updated_at' => '2025-06-20 11:00:00',
            ],

            // Return 4: PTR-20250625-004 (purchase_return_id: 4)
            // Trả 3 Bánh quy bơ (product_id: 2) từ PO-20240611-012 (purchase_order_item_id: 16)
            [
                'purchase_return_id' => 4,
                'purchase_order_item_id' => 16, // Item cho Bánh quy bơ trong PO-20240611-012
                'product_id' => 2,
                'batch_number' => 'BQB-202506-007',
                'manufacturing_date' => '2025-06-05',
                'expiry_date' => '2026-06-05',
                'product_name' => 'Bánh quy bơ hộp 200g',
                'product_sku' => 'G7-BQB-012',
                'quantity_returned' => 3,
                'unit_cost' => 20000,
                'subtotal' => 60000, // 3 * 20000
                'reason' => 'Hàng không đúng mẫu mã',
                'created_at' => '2025-06-25 14:00:00',
                'updated_at' => '2025-06-25 14:00:00',
            ],
        ];

        foreach ($purchase_return_items as $purchase_return_item) {
            PurchaseReturnItem::create($purchase_return_item);
        }
    }
}
