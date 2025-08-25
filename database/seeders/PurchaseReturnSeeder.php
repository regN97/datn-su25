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
                'purchase_order_id' => 3, // PO-20250801-003 (PepsiCo)
                'supplier_id' => 3, // PepsiCo
                'status' => 'completed',
                'return_date' => '2025-08-16', // Sau ngày nhận 15/8
                'reason' => 'Sản phẩm bị móp méo trong quá trình vận chuyển',
                'total_items_returned' => 350, // 200 Coca + 150 Pepsi
                'total_value_returned' => 2450000, // 1.400.000 + 1.050.000
                'created_by' => 1,
                'created_at' => '2025-08-16 10:30:00',
                'updated_at' => '2025-08-16 11:00:00',
            ],
            [
                'return_number' => 'RET-20250606-002',
                'purchase_order_id' => 4, // PO-20250810-004 (Nestlé)
                'supplier_id' => 4, // Nestlé
                'status' => 'completed',
                'return_date' => '2025-08-23', // Sau ngày nhận 22/8
                'reason' => 'Sản phẩm bị móp méo và rò rỉ',
                'total_items_returned' => 450, // 300 LaVie + 150 Birdy
                'total_value_returned' => 2700000, // 1.200.000 + 1.500.000
                'created_by' => 1,
                'created_at' => '2025-08-23 14:30:00',
                'updated_at' => '2025-08-23 15:00:00',
            ],
        ];

        foreach ($returns as $return) {
            PurchaseReturn::create($return);
        }
    }
}
