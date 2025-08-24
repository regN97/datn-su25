<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchase_orders = [
            [
                'po_number' => 'PO-20250601-001',
                'supplier_id' => 1, // Unilever
                'status_id' => 1, // RECEIVED
                'order_date' => '2025-06-01',
                'expected_delivery_date' => '2025-06-04',
                'actual_delivery_date' => '2025-06-01',
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'total_amount' => 2500000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-06-01',
                'notes' => 'Đơn hàng đã nhận đủ và thanh toán đủ',
                'created_at' => '2025-06-01 08:00:00',
                'updated_at' => '2025-06-01 09:00:00',
            ],
            [
                'po_number' => 'PO-20250603-002',
                'supplier_id' => 2, // Vinamilk
                'status_id' => 2, // RECEIVED
                'order_date' => '2025-06-03',
                'expected_delivery_date' => '2025-06-07',
                'actual_delivery_date' => '2025-06-05',
                'discount_type' => 'percent',
                'discount_amount' => 5, // 5%
                'total_amount' => 3800000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-06-03',
                'notes' => 'Đơn hàng đã nhận đủ, thanh toán một phần',
                'created_at' => '2025-06-03 10:30:00',
                'updated_at' => '2025-06-05 14:30:00',
            ],
            [
                'po_number' => 'PO-20250605-003',
                'supplier_id' => 3, // PepsiCo
                'status_id' => 3, // SENT
                'order_date' => '2025-06-05',
                'expected_delivery_date' => '2025-06-10',
                'actual_delivery_date' => null,
                'discount_type' => 'amount',
                'discount_amount' => 50000,
                'total_amount' => 1500000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-06-05',
                'notes' => 'Đơn hàng đã gửi cho nhà cung cấp',
                'created_at' => '2025-06-05 09:15:00',
                'updated_at' => '2025-06-05 09:15:00',
            ],
            [
                'po_number' => 'PO-20250608-004',
                'supplier_id' => 4, // Nestlé
                'status_id' => 3, // RECEIVED
                'order_date' => '2025-06-08',
                'expected_delivery_date' => '2025-06-12',
                'actual_delivery_date' => '2025-06-10',
                'discount_type' => 'percent',
                'discount_amount' => 10, // 10%
                'total_amount' => 4200000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-06-08',
                'notes' => 'Đơn hàng đã nhận đủ và thanh toán đủ',
                'created_at' => '2025-06-08 15:45:00',
                'updated_at' => '2025-06-10 16:45:00',
            ],
            [
                'po_number' => 'PO-20250610-005',
                'supplier_id' => 5, // Sunhouse
                'status_id' => 4, // PENDING
                'order_date' => '2025-06-10',
                'expected_delivery_date' => '2025-06-15',
                'actual_delivery_date' => null,
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'total_amount' => 3000000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-06-10',
                'notes' => 'Đơn hàng mới, đang chờ duyệt',
                'created_at' => '2025-06-10 11:30:00',
                'updated_at' => '2025-06-10 11:30:00',
            ],
        ];

        foreach ($purchase_orders as $order) {
            PurchaseOrder::create($order);
        }
    }
}
