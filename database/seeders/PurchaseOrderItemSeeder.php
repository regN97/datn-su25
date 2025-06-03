<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchase_order_items = [
            [
                'purchase_order_id' => 1, // PO-20240601-001
                'product_id' => 1,
                'product_name' => 'Mì ly hảo hạng 65g',
                'product_sku' => 'G7-MLH-011',
                'quantity_ordered' => 50,
                'quantity_received' => 50,
                'quantity_returned' => 0,
                'unit_cost' => 5000,
                'subtotal' => 250000, // 50 * 5000
                'tax_amount' => 25000, // 10% của subtotal
                'discount_amount' => 0,
                'notes' => 'Giao hàng đúng hạn',
                'created_at' => '2025-06-01 08:00:00',
                'updated_at' => '2025-06-01 08:00:00',
            ],
            [
                'purchase_order_id' => 1, // PO-20240601-001
                'product_id' => 3,
                'product_name' => 'Nước cam ép 330ml',
                'product_sku' => 'G7-NCE-013',
                'quantity_ordered' => 30,
                'quantity_received' => 30,
                'quantity_returned' => 0,
                'unit_cost' => 7000,
                'subtotal' => 210000, // 30 * 7000
                'tax_amount' => 21000,
                'discount_amount' => 0,
                'notes' => '',
                'created_at' => '2025-06-01 08:00:00',
                'updated_at' => '2025-06-01 08:00:00',
            ],
            [
                'purchase_order_id' => 2, // PO-20240601-002
                'product_id' => 5,
                'product_name' => 'Bình đun siêu tốc 1.8L',
                'product_sku' => 'G7-BDST-015',
                'quantity_ordered' => 10,
                'quantity_received' => 9,
                'quantity_returned' => 1,
                'unit_cost' => 180000,
                'subtotal' => 1800000, // 10 * 180000
                'tax_amount' => 180000,
                'discount_amount' => 50000,
                'notes' => 'Lỗi 1 bình cần đổi',
                'created_at' => '2025-06-02 09:00:00',
                'updated_at' => '2025-06-02 09:30:00',
            ],
            [
                'purchase_order_id' => 3, // PO-20240602-003
                'product_id' => 7,
                'product_name' => 'Sữa tắm hương hoa 800ml',
                'product_sku' => 'G7-STHH-017',
                'quantity_ordered' => 20,
                'quantity_received' => 20,
                'quantity_returned' => 0,
                'unit_cost' => 45000,
                'subtotal' => 900000, // 20 * 45000
                'tax_amount' => 90000,
                'discount_amount' => 0,
                'notes' => 'Khuyến mãi',
                'created_at' => '2025-06-03 10:00:00',
                'updated_at' => '2025-06-03 10:00:00',
            ],
            [
                'purchase_order_id' => 3, // PO-20240602-003
                'product_id' => 8,
                'product_name' => 'Kem đánh răng dược liệu 150g',
                'product_sku' => 'G7-KDTRL-018',
                'quantity_ordered' => 15,
                'quantity_received' => 15,
                'quantity_returned' => 0,
                'unit_cost' => 20000,
                'subtotal' => 300000, // 15 * 20000
                'tax_amount' => 30000,
                'discount_amount' => 0,
                'notes' => '',
                'created_at' => '2025-06-03 10:00:00',
                'updated_at' => '2025-06-03 10:00:00',
            ],
            [
                'purchase_order_id' => 4, // PO-20240603-004
                'product_id' => 2,
                'product_name' => 'Bánh quy bơ hộp 200g',
                'product_sku' => 'G7-BQB-012',
                'quantity_ordered' => 40,
                'quantity_received' => 40,
                'quantity_returned' => 0,
                'unit_cost' => 20000,
                'subtotal' => 800000, // 40 * 20000
                'tax_amount' => 80000,
                'discount_amount' => 10000,
                'notes' => 'Đóng gói cẩn thận',
                'created_at' => '2025-06-04 08:00:00',
                'updated_at' => '2025-06-04 08:00:00',
            ],
            [
                'purchase_order_id' => 4, // PO-20240603-004
                'product_id' => 4,
                'product_name' => 'Cà phê lon đen đá 240ml',
                'product_sku' => 'G7-CFL-014',
                'quantity_ordered' => 35,
                'quantity_received' => 35,
                'quantity_returned' => 0,
                'unit_cost' => 9000,
                'subtotal' => 315000, // 35 * 9000
                'tax_amount' => 31500,
                'discount_amount' => 0,
                'notes' => '',
                'created_at' => '2025-06-04 08:00:00',
                'updated_at' => '2025-06-04 08:00:00',
            ],
            [
                'purchase_order_id' => 5, // PO-20240604-005
                'product_id' => 6,
                'product_name' => 'Máy xay sinh tố mini',
                'product_sku' => 'G7-MXST-016',
                'quantity_ordered' => 12,
                'quantity_received' => 12,
                'quantity_returned' => 0,
                'unit_cost' => 220000,
                'subtotal' => 2640000, // 12 * 220000
                'tax_amount' => 264000,
                'discount_amount' => 0,
                'notes' => 'Giao hàng nhanh',
                'created_at' => '2025-06-05 09:00:00',
                'updated_at' => '2025-06-05 09:00:00',
            ],
            [
                'purchase_order_id' => 5, // PO-20240604-005
                'product_id' => 9,
                'product_name' => 'Chổi lau nhà 360 độ',
                'product_sku' => 'G7-CLN-019',
                'quantity_ordered' => 25,
                'quantity_received' => 25,
                'quantity_returned' => 0,
                'unit_cost' => 100000,
                'subtotal' => 2500000, // 25 * 100000
                'tax_amount' => 250000,
                'discount_amount' => 0,
                'notes' => '',
                'created_at' => '2025-06-05 09:00:00',
                'updated_at' => '2025-06-05 09:00:00',
            ],
            [
                'purchase_order_id' => 6, // PO-20240605-006
                'product_id' => 10,
                'product_name' => 'Giẻ lau đa năng 3 cái/gói',
                'product_sku' => 'G7-GLDN-020',
                'quantity_ordered' => 60,
                'quantity_received' => 60,
                'quantity_returned' => 0,
                'unit_cost' => 25000,
                'subtotal' => 1500000, // 60 * 25000
                'tax_amount' => 150000,
                'discount_amount' => 0,
                'notes' => 'Chất lượng tốt',
                'created_at' => '2025-06-06 10:00:00',
                'updated_at' => '2025-06-06 10:00:00',
            ],
            [
                'purchase_order_id' => 7, // PO-20240606-007
                'product_id' => 1,
                'product_name' => 'Mì ly hảo hạng 65g',
                'product_sku' => 'G7-MLH-011',
                'quantity_ordered' => 20,
                'quantity_received' => 18,
                'quantity_returned' => 0,
                'unit_cost' => 5000,
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'discount_amount' => 0,
                'notes' => 'Giao thiếu 2 sản phẩm',
                'created_at' => '2025-06-06 09:00:00',
                'updated_at' => '2025-06-06 09:00:00',
            ],
            [
                'purchase_order_id' => 8, // PO-20240607-008
                'product_id' => 3,
                'product_name' => 'Nước cam ép 330ml',
                'product_sku' => 'G7-NCE-013',
                'quantity_ordered' => 50,
                'quantity_received' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 7000,
                'subtotal' => 350000,
                'tax_amount' => 35000,
                'discount_amount' => 0,
                'notes' => 'Đơn hàng mới',
                'created_at' => '2025-06-07 11:00:00',
                'updated_at' => '2025-06-07 11:00:00',
            ],
            [
                'purchase_order_id' => 9, // PO-20240608-009
                'product_id' => 5,
                'product_name' => 'Bình đun siêu tốc 1.8L',
                'product_sku' => 'G7-BDST-015',
                'quantity_ordered' => 5,
                'quantity_received' => 5,
                'quantity_returned' => 0,
                'unit_cost' => 180000,
                'subtotal' => 900000,
                'tax_amount' => 90000,
                'discount_amount' => 10000,
                'notes' => 'Ưu đãi đặc biệt',
                'created_at' => '2025-06-08 10:00:00',
                'updated_at' => '2025-06-16 14:00:00',
            ],
            [
                'purchase_order_id' => 10, // PO-20240609-010
                'product_id' => 7,
                'product_name' => 'Sữa tắm hương hoa 800ml',
                'product_sku' => 'G7-STHH-017',
                'quantity_ordered' => 30,
                'quantity_received' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 45000,
                'subtotal' => 1350000,
                'tax_amount' => 135000,
                'discount_amount' => 0,
                'notes' => 'Đang chờ xử lý',
                'created_at' => '2025-06-09 08:30:00',
                'updated_at' => '2025-06-09 08:30:00',
            ],
            [
                'purchase_order_id' => 11, // PO-20240610-011
                'product_id' => 9,
                'product_name' => 'Chổi lau nhà 360 độ',
                'product_sku' => 'G7-CLN-019',
                'quantity_ordered' => 10,
                'quantity_received' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 100000,
                'subtotal' => 1000000,
                'tax_amount' => 100000,
                'discount_amount' => 0,
                'notes' => 'Đơn hàng đã hủy',
                'created_at' => '2025-06-10 10:00:00',
                'updated_at' => '2025-06-10 10:00:00',
            ],
            [
                'purchase_order_id' => 12, // PO-20240611-012
                'product_id' => 2,
                'product_name' => 'Bánh quy bơ hộp 200g',
                'product_sku' => 'G7-BQB-012',
                'quantity_ordered' => 25,
                'quantity_received' => 25,
                'quantity_returned' => 0,
                'unit_cost' => 20000,
                'subtotal' => 500000,
                'tax_amount' => 50000,
                'discount_amount' => 5000,
                'notes' => 'Đã giao và nhận đủ',
                'created_at' => '2025-06-11 08:00:00',
                'updated_at' => '2025-06-18 16:00:00',
            ],
            [
                'purchase_order_id' => 13, // PO-20240612-013
                'product_id' => 4,
                'product_name' => 'Cà phê lon đen đá 240ml',
                'product_sku' => 'G7-CFL-014',
                'quantity_ordered' => 40,
                'quantity_received' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 9000,
                'subtotal' => 360000,
                'tax_amount' => 36000,
                'discount_amount' => 0,
                'notes' => 'Đơn hàng lớn, đang chờ giao',
                'created_at' => '2025-06-12 11:00:00',
                'updated_at' => '2025-06-12 11:00:00',
            ],
            [
                'purchase_order_id' => 14, // PO-20240613-014
                'product_id' => 6,
                'product_name' => 'Máy xay sinh tố mini',
                'product_sku' => 'G7-MXST-016',
                'quantity_ordered' => 8,
                'quantity_received' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 220000,
                'subtotal' => 1760000,
                'tax_amount' => 176000,
                'discount_amount' => 0,
                'notes' => 'Sản phẩm đặc biệt',
                'created_at' => '2025-06-13 08:00:00',
                'updated_at' => '2025-06-13 08:00:00',
            ],
            [
                'purchase_order_id' => 15, // PO-20240614-015
                'product_id' => 8,
                'product_name' => 'Kem đánh răng dược liệu 150g',
                'product_sku' => 'G7-KDTRL-018',
                'quantity_ordered' => 30,
                'quantity_received' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 20000,
                'subtotal' => 600000,
                'tax_amount' => 60000,
                'discount_amount' => 0,
                'notes' => 'Đơn hàng cuối tháng',
                'created_at' => '2025-06-14 10:00:00',
                'updated_at' => '2025-06-14 10:00:00',
            ],
        ];

        foreach ($purchase_order_items as $purchase_order_item) {
            PurchaseOrderItem::create($purchase_order_item);
        }
    }
}
