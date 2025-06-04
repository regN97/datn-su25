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
                'purchase_order_id' => 8, // PO-20240607-008: Nước cam ép 330ml (product_id: 3), đặt 50 cái
                'total_items_received' => 50, // Đã nhận đủ 50 cái
                'total_value_received' => 350000, // 50 * 7000 = 350000
                'is_partial' => 'false',
                'receipt_date' => '2025-06-19',
                'received_by' => 1, // Đã có người nhận
                'notes' => 'Phiếu nhập kho đầy đủ cho Nước cam ép từ PO-20240607-008.',
                'created_at' => '2025-06-19 08:00:00',
                'updated_at' => '2025-06-19 08:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250608-008',
                'purchase_order_id' => 13, // PO-20240612-013: Cà phê lon đen đá 240ml (product_id: 4), đặt 40 cái
                'total_items_received' => 40, // Đã nhận đủ 40 cái
                'total_value_received' => 360000, // 40 * 9000 = 360000
                'is_partial' => 'false',
                'receipt_date' => '2025-06-20',
                'received_by' => 1, // Đã có người nhận
                'notes' => 'Phiếu nhập kho đầy đủ cho Cà phê lon từ PO-20240612-013.',
                'created_at' => '2025-06-20 09:00:00',
                'updated_at' => '2025-06-20 09:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250609-009',
                'purchase_order_id' => 14, // PO-20240613-014: Máy xay sinh tố mini (product_id: 6), đặt 8 cái
                'total_items_received' => 8, // Đã nhận đủ 8 cái
                'total_value_received' => 1760000, // 8 * 220000 = 1760000
                'is_partial' => 'false',
                'receipt_date' => '2025-06-21',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho đầy đủ cho Máy xay sinh tố từ PO-20240613-014.',
                'created_at' => '2025-06-21 10:00:00',
                'updated_at' => '2025-06-21 10:00:00',
            ],
            [
                'receipt_number' => 'PNK-20250610-010',
                'purchase_order_id' => 15, // PO-20240614-015: Kem đánh răng dược liệu 150g (product_id: 8), đặt 30 cái
                'total_items_received' => 30, // Đã nhận đủ 30 cái
                'total_value_received' => 600000, // 30 * 20000 = 600000
                'is_partial' => 'false',
                'receipt_date' => '2025-06-22',
                'received_by' => 1,
                'notes' => 'Phiếu nhập kho đầy đủ cho Kem đánh răng từ PO-20240614-015.',
                'created_at' => '2025-06-22 11:00:00',
                'updated_at' => '2025-06-22 11:00:00',
            ],
        ];

        foreach ($purchase_receipts as $purchase_receipt) {
            PurchaseReceipt::create($purchase_receipt);
        }
    }
}
