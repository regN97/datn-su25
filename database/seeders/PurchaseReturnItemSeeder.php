<?php

namespace Database\Seeders;

use App\Models\PurchaseReturnItem;
use Illuminate\Database\Seeder;

class PurchaseReturnItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $returnItems = [
            // Trả hàng từ REC-20250801-201 (Batch 1)
            [
                'purchase_return_id' => 1,
                'purchase_order_item_id' => 5, // ID từ PO-20250801-003
                'product_id' => 18, // Coca Cola Original 330ml
                'batch_number' => 'RET-20250602-001',
                'manufacturing_date' => '2025-07-15',
                'expiry_date' => '2026-07-15',
                'product_name' => 'Coca Cola Original 330ml',
                'product_sku' => 'CCL-001',
                'unit_cost' => 7000,
                'quantity_returned' => 200,
                'subtotal' => 1400000, // 200 * 7000
                'reason' => 'Lon bị móp trong quá trình vận chuyển',
                'created_at' => '2025-08-16 10:30:00', // Sau ngày nhận hàng 15/8
                'updated_at' => '2025-08-16 11:00:00',
            ],
            [
                'purchase_return_id' => 1,
                'purchase_order_item_id' => 6, // ID từ PO-20250801-003
                'product_id' => 19, // Pepsi Black Lime 330ml
                'batch_number' => 'RET-20250602-001',
                'manufacturing_date' => '2025-07-15',
                'expiry_date' => '2026-07-15',
                'product_name' => 'Pepsi Black Lime 330ml',
                'product_sku' => 'PBL-001',
                'unit_cost' => 7000,
                'quantity_returned' => 150,
                'subtotal' => 1050000, // 150 * 7000
                'reason' => 'Lon bị móp trong quá trình vận chuyển',
                'created_at' => '2025-08-16 10:30:00',
                'updated_at' => '2025-08-16 11:00:00',
            ],

            // Trả hàng từ REC-20250810-202 (Batch 2)
            [
                'purchase_return_id' => 2,
                'purchase_order_item_id' => 7, // ID từ PO-20250810-004
                'product_id' => 21, // Nước khoáng LaVie
                'batch_number' => 'RET-20250606-002',
                'manufacturing_date' => '2025-07-15',
                'expiry_date' => '2026-07-15',
                'product_name' => 'Nước khoáng LaVie 500ml',
                'product_sku' => 'LV-001',
                'unit_cost' => 4000,
                'quantity_returned' => 300,
                'subtotal' => 1200000, // 300 * 4000
                'reason' => 'Chai bị rò rỉ',
                'created_at' => '2025-08-23 14:30:00', // Sau ngày nhận hàng 22/8
                'updated_at' => '2025-08-23 15:00:00',
            ],
            [
                'purchase_return_id' => 2,
                'purchase_order_item_id' => 8, // ID từ PO-20250810-004
                'product_id' => 24, // Cà phê lon Birdy sữa
                'batch_number' => 'RET-20250606-002',
                'manufacturing_date' => '2025-07-15',
                'expiry_date' => '2026-07-15',
                'product_name' => 'Cà phê lon Birdy sữa 170ml',
                'product_sku' => 'BS-001',
                'unit_cost' => 10000,
                'quantity_returned' => 150,
                'subtotal' => 1500000, // 150 * 10000
                'reason' => 'Lon bị móp, seal không nguyên vẹn',
                'created_at' => '2025-08-23 14:30:00',
                'updated_at' => '2025-08-23 15:00:00',
            ],
        ];

        foreach ($returnItems as $item) {
            PurchaseReturnItem::create($item);
        }
    }
}
