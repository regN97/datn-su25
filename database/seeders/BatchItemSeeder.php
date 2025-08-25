<?php

namespace Database\Seeders;

use App\Models\BatchItem;
use Illuminate\Database\Seeder;

class BatchItemSeeder extends Seeder
{
    public function run(): void
    {
        $batchItems = [
            // Batch 1 (REC-20250801-201) items - From PO-20250801-003
            [
                'batch_id' => 1,
                'product_id' => 18, // Coca Cola Original 330ml
                'purchase_order_item_id' => 5, // Từ PO-20250801-003
                'ordered_quantity' => 1000,
                'received_quantity' => 1000,
                'rejected_quantity' => 0,
                'remaining_quantity' => 0,
                'current_quantity' => 1000,
                'purchase_price' => 7000,
                'total_amount' => 700000,
                'manufacturing_date' => '2024-07-15',
                'expiry_date' => '2025-08-30',
                'inventory_status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-08-15 09:15:00',
                'updated_at' => '2025-08-15 09:15:00',
            ],
            [
                'batch_id' => 1,
                'product_id' => 19, // Pepsi Black Lime 330ml
                'purchase_order_item_id' => 6, // Từ PO-20250801-003
                'ordered_quantity' => 1000,
                'received_quantity' => 1000,
                'rejected_quantity' => 0,
                'remaining_quantity' => 0,
                'current_quantity' => 1000,
                'purchase_price' => 7000,
                'total_amount' => 700000,
                'manufacturing_date' => '2024-07-15',
                'expiry_date' => '2025-08-30',
                'inventory_status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-08-15 09:15:00',
                'updated_at' => '2025-08-15 09:15:00',
            ],

            // Batch 2 (REC-20250810-202) items - From PO-20250810-004
            [
                'batch_id' => 2,
                'product_id' => 20, // Nước khoáng thiên nhiên LaVie
                'purchase_order_item_id' => 7, // Từ PO-20250810-004
                'ordered_quantity' => 2000,
                'received_quantity' => 2000,
                'rejected_quantity' => 0,
                'remaining_quantity' => 0,
                'current_quantity' => 2000,
                'purchase_price' => 4000, // Theo giá trong PurchaseOrderItem
                'total_amount' => 8000000,
                'manufacturing_date' => '2025-07-15',
                'expiry_date' => '2026-07-15',
                'inventory_status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-08-22 16:45:00',
                'updated_at' => '2025-08-22 16:45:00',
            ],
            [
                'batch_id' => 2,
                'product_id' => 22, // Cà phê lon Birdy sữa 170ml
                'purchase_order_item_id' => 8, // Từ PO-20250810-004
                'ordered_quantity' => 500,
                'received_quantity' => 500,
                'rejected_quantity' => 0,
                'remaining_quantity' => 0,
                'current_quantity' => 500,
                'purchase_price' => 10000, // Theo giá trong PurchaseOrderItem
                'total_amount' => 5000000,
                'manufacturing_date' => '2025-07-15',
                'expiry_date' => '2026-07-15',
                'inventory_status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-08-22 16:45:00',
                'updated_at' => '2025-08-22 16:45:00',
            ],
        ];

        foreach ($batchItems as $item) {
            BatchItem::create($item);
        }
    }
}
