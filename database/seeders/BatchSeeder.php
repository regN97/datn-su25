<?php

namespace Database\Seeders;

use App\Models\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            [
                'batch_number' => 'REC-20250601-101',
                'purchase_order_id' => 1,
                'supplier_id' => 1,
                'received_date' => '2025-06-02',
                'invoice_number' => 'INV-20250602-A01',
                'discount_amount' => 5,
                'discount_type' => 'percent',
                'total_amount' => 2750000,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'payment_date' => '2025-06-02',
                'paid_amount' => 2750000,
                'receipt_status' => 'completed',
                'notes' => 'Lô hàng nhập theo đơn đặt số PO-001, đã thanh toán đầy đủ.',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-02 08:30:00',
                'updated_at' => '2025-06-02 08:30:00',
            ],
            [
                'batch_number' => 'REC-20250601-102',
                'purchase_order_id' => 2,
                'supplier_id' => 2,
                'received_date' => '2025-06-06',
                'invoice_number' => 'INV-20250606-B02',
                'discount_amount' => 300000,
                'discount_type' => 'amount',
                'total_amount' => 3400000,
                'payment_status' => 'partially_paid',
                'payment_method' => 'bank_transfer',
                'payment_date' => '2025-06-06',
                'paid_amount' => 1500000,
                'receipt_status' => 'completed',
                'notes' => 'Thanh toán một phần theo đơn PO-002.',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-06 10:00:00',
                'updated_at' => '2025-06-06 10:00:00',
            ],
            [
                'batch_number' => 'REC-20250601-103',
                'purchase_order_id' => 3,
                'supplier_id' => 3,
                'received_date' => '2025-06-09',
                'invoice_number' => 'INV-20250609-C03',
                'discount_amount' => 0,
                'discount_type' => null,
                'total_amount' => 1800000,
                'payment_status' => 'unpaid',
                'payment_method' => null,
                'payment_date' => null,
                'paid_amount' => 0,
                'receipt_status' => 'partially_received',
                'notes' => 'Chưa thanh toán. Hàng mới nhận một phần.',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-09 14:45:00',
                'updated_at' => '2025-06-09 14:45:00',
            ],
            [
                'batch_number' => 'REC-20250601-104',
                'purchase_order_id' => 4,
                'supplier_id' => 4,
                'received_date' => '2025-06-12',
                'invoice_number' => 'INV-20250612-D04',
                'discount_amount' => 10,
                'discount_type' => 'percent',
                'total_amount' => 4600000,
                'payment_status' => 'paid',
                'payment_method' => 'credit_card',
                'payment_date' => '2025-06-12',
                'paid_amount' => 4600000,
                'receipt_status' => 'completed',
                'notes' => 'Thanh toán đầy đủ bằng thẻ, đã kiểm tra hàng hóa.',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-12 15:00:00',
                'updated_at' => '2025-06-12 15:00:00',
            ],
        ];

        foreach ($batches as $batch) {
            Batch::create($batch);
        }
    }
}
