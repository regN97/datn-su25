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
                'po_number' => 'PO-20250701-001',
                'supplier_id' => 1, // Unilever
                'status_id' => 1, // NHÁP
                'order_date' => '2025-07-01',
                'expected_delivery_date' => '2025-07-10',
                'actual_delivery_date' => '2025-07-13',
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'total_amount' => 1750000 + 500000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-07-01',
                'notes' => 'Đơn hàng đã nhận đủ và thanh toán đủ',
                'created_at' => '2025-07-01 08:00:00',
                'updated_at' => '2025-07-13 09:00:00',
            ],
            [
                'po_number' => 'PO-20250715-002',
                'supplier_id' => 2, // Vinamilk
                'status_id' => 2, // RECEIVED
                'order_date' => '2025-07-15',
                'expected_delivery_date' => '2025-07-22',
                'actual_delivery_date' => '2025-07-28',
                'discount_type' => 'percent',
                'discount_amount' => 5, // 5%
                'total_amount' => 3100000 + 360000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-07-15',
                'notes' => 'Đơn hàng đã nhận đủ, thanh toán một phần',
                'created_at' => '2025-07-15 10:30:00',
                'updated_at' => '2025-07-28 14:30:00',
            ],
            [
                'po_number' => 'PO-20250801-003',
                'supplier_id' => 3, // PepsiCo
                'status_id' => 3, // SENT
                'order_date' => '2025-08-01',
                'expected_delivery_date' => '2025-08-10',
                'actual_delivery_date' => '2025-08-15',
                'discount_type' => 'amount',
                'discount_amount' => 50000,
                'total_amount' => 1350000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-08-01',
                'notes' => 'Đơn hàng đã gửi cho nhà cung cấp',
                'created_at' => '2025-08-01 09:15:00',
                'updated_at' => '2025-08-15 09:15:00',
            ],
            [
                'po_number' => 'PO-20250810-004',
                'supplier_id' => 4, // Nestlé
                'status_id' => 3, // RECEIVED
                'order_date' => '2025-08-10',
                'expected_delivery_date' => '2025-08-18',
                'actual_delivery_date' => '2025-08-22',
                'discount_type' => 'percent',
                'discount_amount' => 10, // 10%
                'total_amount' => 11700000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-08-10',
                'notes' => 'Đơn hàng đã nhận đủ và thanh toán đủ',
                'created_at' => '2025-08-10 15:45:00',
                'updated_at' => '2025-08-22 16:45:00',
            ],
            [
                'po_number' => 'PO-20250820-005',
                'supplier_id' => 5, // Sunhouse
                'status_id' => 4, // PENDING
                'order_date' => '2025-08-20',
                'expected_delivery_date' => '2025-08-22',
                'actual_delivery_date' => '2025-08-26',
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'total_amount' => 1500000 + 350000,
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => '2025-08-20',
                'notes' => 'Đơn hàng mới, đang chờ duyệt',
                'created_at' => '2025-08-20 11:30:00',
                'updated_at' => '2025-08-26 11:30:00',
            ],
        ];

        foreach ($purchase_orders as $order) {
            PurchaseOrder::create($order);
        }
    }
}
