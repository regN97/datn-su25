<?php

namespace Database\Seeders;

use App\Models\BatchItem;
use Illuminate\Database\Seeder;

class BatchItemSeeder extends Seeder
{
    public function run(): void
    {
        $batchItems = [
            // Batch 1 items (PO-20250601-001)
            [
                'batch_id' => 1,
                'product_id' => 1,
                'purchase_order_item_id' => 1,
                'ordered_quantity' => 500,
                'received_quantity' => 500,
                'remaining_quantity' => 0,
                'current_quantity' => 480, // Some items sold
                'purchase_price' => 3500,
                'total_amount' => 1750000,
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => '2026-05-01',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'batch_id' => 1,
                'product_id' => 7,
                'purchase_order_item_id' => 2,
                'ordered_quantity' => 200,
                'received_quantity' => 200,
                'remaining_quantity' => 0,
                'current_quantity' => 180, // Some items sold
                'purchase_price' => 25000,
                'total_amount' => 500000,
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => '2026-05-01',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // Batch 2 items (PO-20250603-002)
            [
                'batch_id' => 2,
                'product_id' => 3,
                'purchase_order_item_id' => 3,
                'ordered_quantity' => 50,
                'received_quantity' => 50,
                'remaining_quantity' => 0,
                'current_quantity' => 45, // Some items sold
                'purchase_price' => 620000,
                'total_amount' => 3100000,
                'manufacturing_date' => '2025-04-01',
                'expiry_date' => '2026-04-01',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'batch_id' => 2,
                'product_id' => 4,
                'purchase_order_item_id' => 4,
                'ordered_quantity' => 20,
                'received_quantity' => 20,
                'remaining_quantity' => 0,
                'current_quantity' => 18, // Some items sold
                'purchase_price' => 180000,
                'total_amount' => 360000,
                'manufacturing_date' => '2025-04-01',
                'expiry_date' => '2026-04-01',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // Batch 3 items (PO-20250605-003 - partial receipt)
            [
                'batch_id' => 3,
                'product_id' => 2,
                'purchase_order_item_id' => 5,
                'ordered_quantity' => 1000,
                'received_quantity' => 600,
                'remaining_quantity' => 400,
                'current_quantity' => 580, // Some items sold
                'purchase_price' => 7000,
                'total_amount' => 420000,
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => '2026-05-15',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'batch_id' => 3,
                'product_id' => 8,
                'purchase_order_item_id' => 6,
                'ordered_quantity' => 1000,
                'received_quantity' => 500,
                'remaining_quantity' => 500,
                'current_quantity' => 480, // Some items sold
                'purchase_price' => 7000,
                'total_amount' => 350000,
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => '2026-05-15',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // Batch 4 items (PO-20250608-004)
            [
                'batch_id' => 4,
                'product_id' => 9,
                'purchase_order_item_id' => 7,
                'ordered_quantity' => 2000,
                'received_quantity' => 2000,
                'remaining_quantity' => 0,
                'current_quantity' => 2000,
                'purchase_price' => 4000,
                'total_amount' => 800000,
                'manufacturing_date' => '2025-06-01',
                'expiry_date' => '2026-06-01',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'batch_id' => 4,
                'product_id' => 10,
                'purchase_order_item_id' => 8,
                'ordered_quantity' => 500,
                'received_quantity' => 500,
                'remaining_quantity' => 0,
                'current_quantity' => 500,
                'purchase_price' => 55000,
                'total_amount' => 2750000,
                'manufacturing_date' => '2025-05-20',
                'expiry_date' => '2026-05-20',
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ];

        foreach ($batchItems as $item) {
            BatchItem::create($item);
        }
    }
}
