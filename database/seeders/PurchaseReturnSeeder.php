<?php

namespace Database\Seeders;

use App\Models\PurchaseReturn;
use Illuminate\Database\Seeder;

class PurchaseReturnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $returns = [
            [
                'return_number' => 'RET-20250602-001',
                'purchase_order_id' => 1,
                'supplier_id' => 1,
                'status' => 'completed',
                'return_date' => '2025-06-02',
                'reason' => 'Sản phẩm bị lỗi bao bì',
                'total_items_returned' => 20,
                'total_value_returned' => 70000,
                'created_by' => 1,
                'created_at' => '2025-06-02 10:30:00',
                'updated_at' => '2025-06-02 11:00:00',
            ],
            [
                'return_number' => 'RET-20250606-002',
                'purchase_order_id' => 2,
                'supplier_id' => 2,
                'status' => 'completed',
                'return_date' => '2025-06-06',
                'reason' => 'Sản phẩm không đúng quy cách',
                'total_items_returned' => 2,
                'total_value_returned' => 1240000,
                'created_by' => 1,
                'created_at' => '2025-06-06 14:30:00',
                'updated_at' => '2025-06-06 15:00:00',
            ],
            [
                'return_number' => 'RET-20250610-003',
                'purchase_order_id' => 3,
                'supplier_id' => 3,
                'status' => 'pending',
                'return_date' => '2025-06-10',
                'reason' => 'Sản phẩm bị móp lon',
                'total_items_returned' => 50,
                'total_value_returned' => 350000,
                'created_by' => 1,
                'created_at' => '2025-06-10 09:15:00',
                'updated_at' => '2025-06-10 09:15:00',
            ],
        ];

        foreach ($returns as $return) {
            PurchaseReturn::create($return);
        }
    }
}
