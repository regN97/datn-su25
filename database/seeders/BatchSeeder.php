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
                'batch_number' => 'RCPT-202506-001',
                'purchase_order_id' => 1,
                'supplier_id' => 1,
                'received_date' => '2025-06-01',
                'invoice_number' => 'INV-20250601-001',
                'total_amount' => 2500000,
                'payment_status' => 'paid',
                'payment_method' => 'bank_transfer',
                'payment_date' => '2025-06-01',
                'paid_amount' => 2500000,
                'receipt_status' => 'completed',
                'notes' => 'Nhập hàng đầy đủ theo PO-20250601-001',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-01 09:00:00',
                'updated_at' => '2025-06-01 09:00:00',
            ],
            [
                'batch_number' => 'RCPT-202506-002',
                'purchase_order_id' => 2,
                'supplier_id' => 2,
                'received_date' => '2025-06-05',
                'invoice_number' => 'INV-20250605-001',
                'total_amount' => 3800000,
                'payment_status' => 'partially_paid',
                'payment_method' => 'cash',
                'payment_date' => '2025-06-01',
                'paid_amount' => 2000000,
                'receipt_status' => 'completed',
                'notes' => 'Nhập hàng đầy đủ theo PO-20250603-002',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-05 14:30:00',
                'updated_at' => '2025-06-05 14:30:00',
            ],
            [
                'batch_number' => 'RCPT-202506-003',
                'purchase_order_id' => 3,
                'supplier_id' => 3,
                'received_date' => '2025-06-08',
                'invoice_number' => 'INV-20250608-001',
                'total_amount' => 1500000,
                'payment_status' => 'unpaid',
                'payment_method' => null,
                'payment_date' => '2025-06-01',
                'paid_amount' => 0,
                'receipt_status' => 'partially_received',
                'notes' => 'Nhập hàng một phần theo PO-20250605-003',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-08 11:15:00',
                'updated_at' => '2025-06-08 11:15:00',
            ],
            [
                'batch_number' => 'RCPT-202506-004',
                'purchase_order_id' => 4,
                'supplier_id' => 4,
                'received_date' => '2025-06-10',
                'invoice_number' => 'INV-20250610-001',
                'total_amount' => 4200000,
                'payment_status' => 'paid',
                'payment_method' => 'credit_card',
                'payment_date' => '2025-06-01',
                'paid_amount' => 4200000,
                'receipt_status' => 'completed',
                'notes' => 'Nhập hàng đầy đủ theo PO-20250608-004',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-06-10 16:45:00',
                'updated_at' => '2025-06-10 16:45:00',
            ],
        ];

        foreach ($batches as $batch) {
            Batch::create($batch);
        }
    }
}
