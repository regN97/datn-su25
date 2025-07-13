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
            // Trả hàng cho PO-20250601-001 (Mì Hảo Hảo)
            [
                'purchase_return_id' => 1,
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'batch_number' => 'RCPT-202506-001',
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => '2026-05-01',
                'product_name' => 'Mì Hảo Hảo Tôm Chua Cay 75g',
                'product_sku' => 'MHH-001',
                'unit_cost' => 3500,
                'quantity_returned' => 20,
                'subtotal' => 70000,
                'reason' => 'Bao bì bị rách',
                'created_at' => '2025-06-02 10:30:00',
                'updated_at' => '2025-06-02 11:00:00',
            ],

            // Trả hàng cho PO-20250603-002 (Ensure Gold)
            [
                'purchase_return_id' => 2,
                'purchase_order_item_id' => 3,
                'product_id' => 3,
                'batch_number' => 'RCPT-202506-002',
                'manufacturing_date' => '2025-04-01',
                'expiry_date' => '2026-04-01',
                'product_name' => 'Sữa Ensure Gold 850g',
                'product_sku' => 'EG-001',
                'unit_cost' => 620000,
                'quantity_returned' => 2,
                'subtotal' => 1240000,
                'reason' => 'Hộp bị móp, seal không nguyên vẹn',
                'created_at' => '2025-06-06 14:30:00',
                'updated_at' => '2025-06-06 15:00:00',
            ],

            // Trả hàng cho PO-20250605-003 (Coca Cola)
            [
                'purchase_return_id' => 3,
                'purchase_order_item_id' => 5,
                'product_id' => 2,
                'batch_number' => 'RCPT-202506-003',
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => '2026-05-15',
                'product_name' => 'Coca Cola Original 330ml',
                'product_sku' => 'CCL-001',
                'unit_cost' => 7000,
                'quantity_returned' => 50,
                'subtotal' => 350000,
                'reason' => 'Lon bị móp trong quá trình vận chuyển',
                'created_at' => '2025-06-10 09:15:00',
                'updated_at' => '2025-06-10 09:15:00',
            ],
        ];

        foreach ($returnItems as $item) {
            PurchaseReturnItem::create($item);
        }
    }
}
