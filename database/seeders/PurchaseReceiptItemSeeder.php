<?php

namespace Database\Seeders;

use App\Models\PurchaseReceiptItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseReceiptItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchase_receipt_items = [
            // PRN-20250601-001 (purchase_receipt_id: 1) -> PO-20240601-002 (purchase_order_id: 2)
            // Có 1 item: product_id 5 (Bình đun siêu tốc 1.8L), nhận 9 cái.
            [
                'purchase_receipt_id' => 1,
                'purchase_order_item_id' => 3, // Item cho Bình đun siêu tốc trong PO-20240601-002
                'product_id' => 5,
                'batch_number' => 'BDST-202505-001',
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => null, // Không có hạn sử dụng
                'product_name' => 'Bình đun siêu tốc 1.8L',
                'product_sku' => 'G7-BDST-015',
                'quantity_received' => 9,
                'unit_cost' => 180000,
                'subtotal' => 1620000, // 9 * 180000
                'created_at' => '2025-06-08 10:00:00',
                'updated_at' => '2025-06-08 10:00:00',
            ],

            // PRN-20250602-002 (purchase_receipt_id: 2) -> PO-20240603-004 (purchase_order_id: 4)
            // Có 2 items: product_id 7 (Sữa tắm) nhận 20 cái, product_id 8 (Kem đánh răng) nhận 15 cái
            [
                'purchase_receipt_id' => 2,
                'purchase_order_item_id' => 4, // Item cho Sữa tắm trong PO-20240603-004
                'product_id' => 7,
                'batch_number' => 'STHH-202504-002',
                'manufacturing_date' => '2025-04-10',
                'expiry_date' => '2027-04-10',
                'product_name' => 'Sữa tắm hương hoa 800ml',
                'product_sku' => 'G7-STHH-017',
                'quantity_received' => 20,
                'unit_cost' => 45000,
                'subtotal' => 900000, // 20 * 45000
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],
            [
                'purchase_receipt_id' => 2,
                'purchase_order_item_id' => 5, // Item cho Kem đánh răng trong PO-20240603-004
                'product_id' => 8,
                'batch_number' => 'KDTRL-202503-003',
                'manufacturing_date' => '2025-03-05',
                'expiry_date' => '2026-03-05',
                'product_name' => 'Kem đánh răng dược liệu 150g',
                'product_sku' => 'G7-KDTRL-018',
                'quantity_received' => 15,
                'unit_cost' => 20000,
                'subtotal' => 300000, // 15 * 20000
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],

            // PRN-20250603-003 (purchase_receipt_id: 3) -> PO-20240605-006 (purchase_order_id: 6)
            // Có 1 item: product_id 10 (Giẻ lau) nhận 60 cái
            [
                'purchase_receipt_id' => 3,
                'purchase_order_item_id' => 10, // Item cho Giẻ lau trong PO-20240605-006
                'product_id' => 10,
                'batch_number' => 'GLDN-202505-004',
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => null,
                'product_name' => 'Giẻ lau đa năng 3 cái/gói',
                'product_sku' => 'G7-GLDN-020',
                'quantity_received' => 60,
                'unit_cost' => 25000,
                'subtotal' => 1500000, // 60 * 25000
                'created_at' => '2025-06-11 12:00:00',
                'updated_at' => '2025-06-11 12:00:00',
            ],

            // PRN-20250604-004 (purchase_receipt_id: 4) -> PO-20240606-007 (purchase_order_id: 7)
            // Có 1 item: product_id 1 (Mì ly) nhận 18 cái (đặt 20, nhận thiếu)
            [
                'purchase_receipt_id' => 4,
                'purchase_order_item_id' => 11, // Item cho Mì ly trong PO-20240606-007
                'product_id' => 1,
                'batch_number' => 'MLH-202505-005',
                'manufacturing_date' => '2025-05-20',
                'expiry_date' => '2025-11-20',
                'product_name' => 'Mì ly hảo hạng 65g',
                'product_sku' => 'G7-MLH-011',
                'quantity_received' => 18,
                'unit_cost' => 5000,
                'subtotal' => 90000, // 18 * 5000
                'created_at' => '2025-06-15 09:00:00',
                'updated_at' => '2025-06-15 09:00:00',
            ],

            // PRN-20250605-005 (purchase_receipt_id: 5) -> PO-20240608-009 (purchase_order_id: 9)
            // Có 1 item: product_id 5 (Bình đun siêu tốc) nhận 5 cái
            [
                'purchase_receipt_id' => 5,
                'purchase_order_item_id' => 13, // Item cho Bình đun trong PO-20240608-009
                'product_id' => 5,
                'batch_number' => 'BDST-202506-006',
                'manufacturing_date' => '2025-06-01',
                'expiry_date' => null,
                'product_name' => 'Bình đun siêu tốc 1.8L',
                'product_sku' => 'G7-BDST-015',
                'quantity_received' => 5,
                'unit_cost' => 180000,
                'subtotal' => 900000, // 5 * 180000
                'created_at' => '2025-06-16 10:00:00',
                'updated_at' => '2025-06-16 10:00:00',
            ],

            // PRN-20250606-006 (purchase_receipt_id: 6) -> PO-20240611-012 (purchase_order_id: 12)
            // Có 1 item: product_id 2 (Bánh quy bơ) nhận 25 cái
            [
                'purchase_receipt_id' => 6,
                'purchase_order_item_id' => 16, // Item cho Bánh quy bơ trong PO-20240611-012
                'product_id' => 2,
                'batch_number' => 'BQB-202506-007',
                'manufacturing_date' => '2025-06-05',
                'expiry_date' => '2026-06-05',
                'product_name' => 'Bánh quy bơ hộp 200g',
                'product_sku' => 'G7-BQB-012',
                'quantity_received' => 25,
                'unit_cost' => 20000,
                'subtotal' => 500000, // 25 * 20000
                'created_at' => '2025-06-18 11:00:00',
                'updated_at' => '2025-06-18 11:00:00',
            ],
            // Các phiếu nhập kho còn lại (7, 8, 9, 10) có total_items_received = 0, nên không có item nào được nhận.
            // Do đó, không cần tạo bản ghi purchase_receipt_items cho chúng.
        ];

        foreach ($purchase_receipt_items as $purchase_receipt_item) {
            PurchaseReceiptItem::create($purchase_receipt_item);
        }
    }
}
