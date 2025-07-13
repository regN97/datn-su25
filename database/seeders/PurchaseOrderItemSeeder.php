<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderItem;
use Illuminate\Database\Seeder;

class PurchaseOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Items for PO-20250601-001 (Unilever)
            [
                'purchase_order_id' => 1,
                'product_id' => 1, // Mì Hảo Hảo
                'product_name' => 'Mì Hảo Hảo Tôm Chua Cay 75g',
                'product_sku' => 'MHH-001',
                'ordered_quantity' => 500,
                'received_quantity' => 500,
                'quantity_returned' => 0,
                'unit_cost' => 3500,
                'subtotal' => 1750000,
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'notes' => 'Nhập với số lượng lớn',
            ],
            [
                'purchase_order_id' => 1,
                'product_id' => 7, // Cá hộp
                'product_name' => 'Cá hộp 3 Cô Gái 180g',
                'product_sku' => '3CG-001',
                'ordered_quantity' => 200,
                'received_quantity' => 200,
                'quantity_returned' => 0,
                'unit_cost' => 25000,
                'subtotal' => 500000,
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'notes' => null,
            ],

            // Items for PO-20250603-002 (Vinamilk)
            [
                'purchase_order_id' => 2,
                'product_id' => 3, // Ensure Gold
                'product_name' => 'Sữa Ensure Gold 850g',
                'product_sku' => 'EG-001',
                'ordered_quantity' => 50,
                'received_quantity' => 50,
                'quantity_returned' => 0,
                'unit_cost' => 620000,
                'subtotal' => 3100000,
                'discount_type' => 'percent',
                'discount_amount' => 5, // 5%
                'notes' => 'Đơn hàng số lượng lớn',
            ],
            [
                'purchase_order_id' => 2,
                'product_id' => 4, // Centrum
                'product_name' => 'Centrum Adults 30 viên',
                'product_sku' => 'CTA-001',
                'ordered_quantity' => 20,
                'received_quantity' => 20,
                'quantity_returned' => 0,
                'unit_cost' => 180000,
                'subtotal' => 360000,
                'discount_type' => 'percent',
                'discount_amount' => 5, // 5%
                'notes' => null,
            ],

            // Items for PO-20250605-003 (PepsiCo)
            [
                'purchase_order_id' => 3,
                'product_id' => 2, // Coca Cola
                'product_name' => 'Coca Cola Original 330ml',
                'product_sku' => 'CCL-001',
                'ordered_quantity' => 1000,
                'received_quantity' => 600,
                'quantity_returned' => 0,
                'unit_cost' => 7000,
                'subtotal' => 700000,
                'discount_type' => 'amount',
                'discount_amount' => 50000,
                'notes' => 'Nhận hàng một phần',
            ],
            [
                'purchase_order_id' => 3,
                'product_id' => 8, // Pepsi Black Lime
                'product_name' => 'Pepsi Black Lime 330ml',
                'product_sku' => 'PBL-001',
                'ordered_quantity' => 1000,
                'received_quantity' => 500,
                'quantity_returned' => 0,
                'unit_cost' => 7000,
                'subtotal' => 700000,
                'discount_type' => 'amount',
                'discount_amount' => 50000,
                'notes' => 'Nhận hàng một phần',
            ],

            // Items for PO-20250608-004 (Nestlé)
            [
                'purchase_order_id' => 4,
                'product_id' => 9, // Nước khoáng
                'product_name' => 'Nước khoáng LaVie 500ml',
                'product_sku' => 'LV-001',
                'ordered_quantity' => 2000,
                'received_quantity' => 2000,
                'quantity_returned' => 0,
                'unit_cost' => 4000,
                'subtotal' => 800000,
                'discount_type' => 'percent',
                'discount_amount' => 10, // 10%
                'notes' => null,
            ],
            [
                'purchase_order_id' => 4,
                'product_id' => 10, // Cà phê
                'product_name' => 'Cà phê Trung Nguyên Sáng tạo 2',
                'product_sku' => 'TNST2-001',
                'ordered_quantity' => 500,
                'received_quantity' => 500,
                'quantity_returned' => 0,
                'unit_cost' => 55000,
                'subtotal' => 2750000,
                'discount_type' => 'percent',
                'discount_amount' => 10, // 10%
                'notes' => null,
            ],

            // Items for PO-20250610-005 (Sunhouse)
            [
                'purchase_order_id' => 5,
                'product_id' => 5, // Gạo ST25
                'product_name' => 'Gạo ST25 5kg',
                'product_sku' => 'ST25-001',
                'ordered_quantity' => 100,
                'received_quantity' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 150000,
                'subtotal' => 1500000,
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'notes' => 'Đơn hàng mới',
            ],
            [
                'purchase_order_id' => 5,
                'product_id' => 6, // Mì gói
                'product_name' => 'Mì Hảo Hảo Tôm Chua Cay 75g',
                'product_sku' => 'MHH-001',
                'ordered_quantity' => 1000,
                'received_quantity' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 3500,
                'subtotal' => 350000,
                'discount_type' => 'amount',
                'discount_amount' => 0,
                'notes' => 'Đơn hàng mới',
            ],
        ];

        foreach ($items as $item) {
            PurchaseOrderItem::create($item);
        }
    }
}
