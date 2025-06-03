<?php

namespace Database\Seeders;

use App\Models\PurchaseReceipt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseReceiptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchase_receipts = [
            [
                'receipt_number' => 'PNK-20250601-001',
                'purchase_order_id' => 2, // PO-20240601-002: có 1 item (product_id 5) nhận 9 cái. Giá trị: 1.732.000
                'total_items_received' => 9,
                'total_value_received' => 1732000,
                'is_partial' => 'true',
                'receipt_date' => '2025-06-08',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho một phần cho PO-20240601-002 (nhận 9/10 bình đun).',
                'created_at' => '2025-06-08 10:00:00',
                'updated_at' => '2025-06-08 10:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250602-002',
                'purchase_order_id' => 4, // PO-20240603-004: có 2 items nhận 40+35=75 cái. Giá trị: 870.000 + 346.500 = 1.216.500
                'total_items_received' => 75,
                'total_value_received' => 1216500,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-09',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho đầy đủ cho PO-20240603-004.',
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250603-003',
                'purchase_order_id' => 6, // PO-20240605-006: có 1 item nhận 60 cái. Giá trị: 1.650.000
                'total_items_received' => 60,
                'total_value_received' => 1650000,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-11',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho đầy đủ cho PO-20240605-006.',
                'created_at' => '2025-06-11 12:00:00',
                'updated_at' => '2025-06-11 12:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250604-004',
                'purchase_order_id' => 7, // PO-20240606-007: có 1 item nhận 18 cái. Giá trị: 100.000
                'total_items_received' => 18,
                'total_value_received' => 100000,
                'is_partial' => 'true',
                'receipt_date' => '2025-06-15',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho một phần cho PO-20240606-007 (thiếu 2 sản phẩm).',
                'created_at' => '2025-06-15 09:00:00',
                'updated_at' => '2025-06-15 09:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250605-005',
                'purchase_order_id' => 9, // PO-20240608-009: có 1 item nhận 5 cái. Giá trị: 990.000
                'total_items_received' => 5,
                'total_value_received' => 990000,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-16',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho đầy đủ cho PO-20240608-009.',
                'created_at' => '2025-06-16 10:00:00',
                'updated_at' => '2025-06-16 10:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250606-006',
                'purchase_order_id' => 12, // PO-20240611-012: có 1 item nhận 25 cái. Giá trị: 545.000
                'total_items_received' => 25,
                'total_value_received' => 545000,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-18',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho đầy đủ cho PO-20240611-012.',
                'created_at' => '2025-06-18 11:00:00',
                'updated_at' => '2025-06-18 11:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250607-007',
                'purchase_order_id' => 8, // PO-20240607-008: Chưa nhận hàng
                'total_items_received' => 0,
                'total_value_received' => 0,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-19', // Gán ngày cụ thể, không còn Carbon::now()
                'received_by' => 1,
                'notes' => 'Đơn hàng chưa có phiếu nhập kho.',
                'created_at' => '2025-06-19 08:00:00',
                'updated_at' => '2025-06-19 08:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250608-008',
                'purchase_order_id' => 13, // PO-20240612-013: Chưa nhận hàng
                'total_items_received' => 0,
                'total_value_received' => 0,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-20', // Gán ngày cụ thể
                'received_by' => 1,
                'notes' => 'Đơn hàng đã duyệt nhưng chưa nhận hàng.',
                'created_at' => '2025-06-20 09:00:00',
                'updated_at' => '2025-06-20 09:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250609-009',
                'purchase_order_id' => 14, // PO-20240613-014: Chưa nhận hàng
                'total_items_received' => 0,
                'total_value_received' => 0,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-21', // Gán ngày cụ thể
                'received_by' => 1,
                'notes' => 'Đơn hàng đã gửi nhưng chưa có phiếu nhập kho.',
                'created_at' => '2025-06-21 10:00:00',
                'updated_at' => '2025-06-21 10:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250610-010',
                'purchase_order_id' => 15, // PO-20240614-015: Chưa nhận hàng
                'total_items_received' => 0,
                'total_value_received' => 0,
                'is_partial' => 'false',
                'receipt_date' => '2025-06-22', // Gán ngày cụ thể
                'received_by' => 1,
                'notes' => 'Đơn hàng chờ duyệt, chưa có phiếu nhập kho.',
                'created_at' => '2025-06-22 11:00:00',
                'updated_at' => '2025-06-22 11:00:00',
            ],
        ];

        foreach ($purchase_receipts as $purchase_receipt) {
            PurchaseReceipt::create($purchase_receipt);
        }
    }
}
