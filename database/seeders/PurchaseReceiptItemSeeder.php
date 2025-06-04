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
            // Lô hàng: STHH-202504-002 và KDTRL-202503-003
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
            // Lô hàng: GLDN-202505-004
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
            // Lô hàng: MLH-202505-005
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

            // PRN-20250605-005 (purchase_receipt_id: 5) -> PO-20240604-005 (purchase_order_id: 5)
            // Có 2 items: product_id 6 (Máy xay) nhận 12 cái, product_id 9 (Chổi lau nhà) nhận 25 cái
            // Lô hàng: MXST-202505-001 và CLN-202505-001
            [
                'purchase_receipt_id' => 5,
                'purchase_order_item_id' => 8, // Item cho Máy xay sinh tố trong PO-20240604-005
                'product_id' => 6,
                'batch_number' => 'MXST-202505-001',
                'manufacturing_date' => '2025-05-10',
                'expiry_date' => null,
                'product_name' => 'Máy xay sinh tố mini',
                'product_sku' => 'G7-MXST-016',
                'quantity_received' => 12,
                'unit_cost' => 220000,
                'subtotal' => 2640000, // 12 * 220000
                'created_at' => '2025-06-16 10:00:00',
                'updated_at' => '2025-06-16 10:00:00',
            ],
            [
                'purchase_receipt_id' => 5,
                'purchase_order_item_id' => 9, // Item cho Chổi lau nhà trong PO-20240604-005
                'product_id' => 9,
                'batch_number' => 'CLN-202505-001',
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => null,
                'product_name' => 'Chổi lau nhà 360 độ',
                'product_sku' => 'G7-CLN-019',
                'quantity_received' => 25,
                'unit_cost' => 100000,
                'subtotal' => 2500000, // 25 * 100000
                'created_at' => '2025-06-16 10:00:00',
                'updated_at' => '2025-06-16 10:00:00',
            ],

            // PRN-20250606-006 (purchase_receipt_id: 6) -> PO-20240611-012 (purchase_order_id: 12)
            // Có 1 item: product_id 2 (Bánh quy bơ) nhận 25 cái
            // Lô hàng: BQB-202506-007
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

            // PRN-20250607-007 (purchase_receipt_id: 7) -> PO-20240607-008 (purchase_order_id: 8)
            // Item: Nước cam ép 330ml (product_id: 3) nhận 50 cái
            // Lô hàng: NCE-202506-001 (lô mới)
            [
                'purchase_receipt_id' => 7,
                'purchase_order_item_id' => 12, // Item cho Nước cam ép trong PO-20240607-008
                'product_id' => 3,
                'batch_number' => 'NCE-202506-001',
                'manufacturing_date' => '2025-06-10',
                'expiry_date' => '2026-06-10',
                'product_name' => 'Nước cam ép 330ml',
                'product_sku' => 'G7-NCE-013',
                'quantity_received' => 50,
                'unit_cost' => 7000,
                'subtotal' => 350000, // 50 * 7000
                'created_at' => '2025-06-19 08:00:00',
                'updated_at' => '2025-06-19 08:00:00',
            ],

            // PRN-20250608-008 (purchase_receipt_id: 8) -> PO-20240612-013 (purchase_order_id: 13)
            // Item: Cà phê lon đen đá 240ml (product_id: 4) nhận 40 cái
            // Lô hàng: CFL-202506-001 (lô mới)
            [
                'purchase_receipt_id' => 8,
                'purchase_order_item_id' => 17, // Item cho Cà phê lon trong PO-20240612-013
                'product_id' => 4,
                'batch_number' => 'CFL-202506-001',
                'manufacturing_date' => '2025-06-12',
                'expiry_date' => '2026-06-12',
                'product_name' => 'Cà phê lon đen đá 240ml',
                'product_sku' => 'G7-CFL-014',
                'quantity_received' => 40,
                'unit_cost' => 9000,
                'subtotal' => 360000, // 40 * 9000
                'created_at' => '2025-06-20 09:00:00',
                'updated_at' => '2025-06-20 09:00:00',
            ],

            // PRN-20250609-009 (purchase_receipt_id: 9) -> PO-20240613-014 (purchase_order_id: 14)
            // Item: Máy xay sinh tố mini (product_id: 6) nhận 8 cái
            // Lô hàng: MXST-202505-001 (đã có)
            [
                'purchase_receipt_id' => 9,
                'purchase_order_item_id' => 18, // Item cho Máy xay sinh tố trong PO-20240613-014
                'product_id' => 6,
                'batch_number' => 'MXST-202505-001', // Sử dụng batch_number đã có
                'manufacturing_date' => '2025-05-10',
                'expiry_date' => null,
                'product_name' => 'Máy xay sinh tố mini',
                'product_sku' => 'G7-MXST-016',
                'quantity_received' => 8,
                'unit_cost' => 220000,
                'subtotal' => 1760000, // 8 * 220000
                'created_at' => '2025-06-21 10:00:00',
                'updated_at' => '2025-06-21 10:00:00',
            ],

            // PRN-20250610-010 (purchase_receipt_id: 10) -> PO-20240614-015 (purchase_order_id: 15)
            // Item: Kem đánh răng dược liệu 150g (product_id: 8) nhận 30 cái
            // Lô hàng: KDTRL-202503-003 (đã có)
            [
                'purchase_receipt_id' => 10,
                'purchase_order_item_id' => 19, // Item cho Kem đánh răng trong PO-20240614-015
                'product_id' => 8,
                'batch_number' => 'KDTRL-202503-003', // Sử dụng batch_number đã có
                'manufacturing_date' => '2025-03-05',
                'expiry_date' => '2026-03-05',
                'product_name' => 'Kem đánh răng dược liệu 150g',
                'product_sku' => 'G7-KDTRL-018',
                'quantity_received' => 30,
                'unit_cost' => 20000,
                'subtotal' => 600000, // 30 * 20000
                'created_at' => '2025-06-22 11:00:00',
                'updated_at' => '2025-06-22 11:00:00',
            ],
        ];

        foreach ($purchase_receipt_items as $purchase_receipt_item) {
            PurchaseReceiptItem::create($purchase_receipt_item);
        }
    }
}
