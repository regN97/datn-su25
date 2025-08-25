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
                'batch_number' => 'REC-20250801-201',
                'purchase_order_id' => 3,
                'supplier_id' => 3, // PepsiCo
                'received_date' => '2025-08-15', // Theo actual_delivery_date của PO
                'invoice_number' => 'INV-20250815-C03',
                'discount_amount' => 50000, // Theo PO
                'discount_type' => 'amount', // Theo PO
                'total_amount' => 1400000, // 700000 + 700000
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'payment_date' => '2025-08-15',
                'paid_amount' => 1350000,
                'receipt_status' => 'completed',
                'status' => 'completed',
                'notes' => 'Lô hàng nhập theo đơn đặt số PO-20250801-003, đã thanh toán đầy đủ.',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-08-15 09:15:00', // Theo PO
                'updated_at' => '2025-08-15 09:15:00',
            ],
            [
                'batch_number' => 'REC-20250810-202',
                'purchase_order_id' => 4,
                'supplier_id' => 4, // Nestlé
                'received_date' => '2025-08-22', // Theo actual_delivery_date của PO
                'invoice_number' => 'INV-20250822-D04',
                'discount_amount' => 10, // Theo PO
                'discount_type' => 'percent', // Theo PO
                'total_amount' => 11700000, // 2000000 + 1550000
                'payment_status' => 'paid',
                'payment_method' => 'credit_card',
                'payment_date' => '2025-08-22',
                'paid_amount' => 11700000,
                'receipt_status' => 'completed',
                'status' => 'completed',
                'notes' => 'Thanh toán đầy đủ bằng thẻ, đã kiểm tra hàng hóa. Đơn từ PO-20250810-004.',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-08-22 16:45:00', // Theo PO
                'updated_at' => '2025-08-22 16:45:00',
            ],
        ];

        foreach ($batches as $batch) {
            Batch::create($batch);
        }
    }
}
