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
                'status_id' => 5, // RECEIVED
                'order_date' => '2025-06-01',
                'expected_delivery_date' => '2025-06-04',
                'actual_delivery_date' => '2025-06-01',
                'subtotal_amount' => 2272727,
                'tax_amount' => 227273,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 2500000,
                'payment_status' => 'paid',
                'payment_terms' => 'Thanh toán ngay khi nhận hàng',
                'payment_method' => 'bank_transfer',
                'payment_due_date' => '2025-06-01',
                'amount_paid' => 2500000,
                'balance_due' => 0,
                'created_by' => 1,
                'notes' => 'Đơn hàng đã nhận đủ và thanh toán đủ',
                'created_at' => '2025-06-01 08:00:00',
                'updated_at' => '2025-06-01 09:00:00',
            ],
            [
                'po_number' => 'PO-20250603-002',
                'supplier_id' => 2, // Vinamilk
                'status_id' => 5, // RECEIVED
                'order_date' => '2025-06-03',
                'expected_delivery_date' => '2025-06-07',
                'actual_delivery_date' => '2025-06-05',
                'subtotal_amount' => 3454545,
                'tax_amount' => 345455,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 3800000,
                'payment_status' => 'partially_paid',
                'payment_terms' => 'Thanh toán trong 30 ngày',
                'payment_method' => 'bank_transfer',
                'payment_due_date' => '2025-07-03',
                'amount_paid' => 2000000,
                'balance_due' => 1800000,
                'created_by' => 1,
                'notes' => 'Đơn hàng đã nhận đủ, thanh toán một phần',
                'created_at' => '2025-06-03 10:30:00',
                'updated_at' => '2025-06-05 14:30:00',
            ],
            [
                'po_number' => 'PO-20250605-003',
                'supplier_id' => 3, // PepsiCo
                'status_id' => 4, // SENT
                'order_date' => '2025-06-05',
                'expected_delivery_date' => '2025-06-10',
                'actual_delivery_date' => null,
                'subtotal_amount' => 1363636,
                'tax_amount' => 136364,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 1500000,
                'payment_status' => 'unpaid',
                'payment_terms' => 'Thanh toán sau khi nhận hàng',
                'payment_method' => 'bank_transfer',
                'payment_due_date' => '2025-06-12',
                'amount_paid' => 0,
                'balance_due' => 1500000,
                'created_by' => 1,
                'notes' => 'Đơn hàng đã gửi cho nhà cung cấp',
                'created_at' => '2025-06-05 09:15:00',
                'updated_at' => '2025-06-05 09:15:00',
            ],
            [
                'po_number' => 'PO-20250608-004',
                'supplier_id' => 4, // Nestlé
                'status_id' => 5, // RECEIVED
                'order_date' => '2025-06-08',
                'expected_delivery_date' => '2025-06-12',
                'actual_delivery_date' => '2025-06-10',
                'subtotal_amount' => 3818182,
                'tax_amount' => 381818,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 4200000,
                'payment_status' => 'paid',
                'payment_terms' => 'Thanh toán ngay khi nhận hàng',
                'payment_method' => 'bank_transfer',
                'payment_due_date' => '2025-06-10',
                'amount_paid' => 4200000,
                'balance_due' => 0,
                'created_by' => 1,
                'notes' => 'Đơn hàng đã nhận đủ và thanh toán đủ',
                'created_at' => '2025-06-08 15:45:00',
                'updated_at' => '2025-06-10 16:45:00',
            ],
            [
                'po_number' => 'PO-20250610-005',
                'supplier_id' => 5, // Sunhouse
                'status_id' => 2, // PENDING
                'order_date' => '2025-06-10',
                'expected_delivery_date' => '2025-06-15',
                'actual_delivery_date' => null,
                'subtotal_amount' => 2727273,
                'tax_amount' => 272727,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 3000000,
                'payment_status' => 'unpaid',
                'payment_terms' => 'Thanh toán 50% trước khi giao hàng',
                'payment_method' => 'bank_transfer',
                'payment_due_date' => '2025-06-13',
                'amount_paid' => 0,
                'balance_due' => 3000000,
                'created_by' => 1,
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
