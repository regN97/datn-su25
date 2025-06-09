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
                'batch_number' => 'BDST-202505-001',
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => null,
                'supplier_id' => 2,
                'received_date' => '2025-06-08',
                'invoice_number' => 'PNK-20250601-001',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240601-002',
                'created_at' => '2025-06-08 10:00:00',
                'updated_at' => '2025-06-08 10:00:00',
            ],
            [
                'batch_number' => 'STHH-202504-002',
                'manufacturing_date' => '2025-04-10',
                'expiry_date' => '2027-04-10',
                'supplier_id' => 4,
                'received_date' => '2025-06-09',
                'invoice_number' => 'PNK-20250602-002',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240603-004',
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],
            [
                'batch_number' => 'KDTRL-202503-003',
                'manufacturing_date' => '2025-03-05',
                'expiry_date' => '2026-03-05',
                'supplier_id' => 4,
                'received_date' => '2025-06-09',
                'invoice_number' => 'PNK-20250602-002',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240603-004',
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],
            [
                'batch_number' => 'GLDN-202505-004',
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => null,
                'supplier_id' => 5,
                'received_date' => '2025-06-11',
                'invoice_number' => 'PNK-20250603-003',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240605-006',
                'created_at' => '2025-06-11 12:00:00',
                'updated_at' => '2025-06-11 12:00:00',
            ],
            [
                'batch_number' => 'MLH-202505-005',
                'manufacturing_date' => '2025-05-20',
                'expiry_date' => '2025-11-20',
                'supplier_id' => 6,
                'received_date' => '2025-06-15',
                'invoice_number' => 'PNK-20250604-004',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240606-007 (nhận thiếu)',
                'created_at' => '2025-06-15 09:00:00',
                'updated_at' => '2025-06-15 09:00:00',
            ],
            [
                'batch_number' => 'BDST-202506-006',
                'manufacturing_date' => '2025-06-01',
                'expiry_date' => null,
                'supplier_id' => 8,
                'received_date' => '2025-06-16',
                'invoice_number' => 'PNK-20250605-005',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240608-009',
                'created_at' => '2025-06-16 10:00:00',
                'updated_at' => '2025-06-16 10:00:00',
            ],
            [
                'batch_number' => 'BQB-202506-007',
                'manufacturing_date' => '2025-06-05',
                'expiry_date' => '2026-06-05',
                'supplier_id' => 1,
                'received_date' => '2025-06-18',
                'invoice_number' => 'PNK-20250606-006',
                'status' => 'active',
                'notes' => 'Lô hàng nhập từ PO-20240611-012',
                'created_at' => '2025-06-18 11:00:00',
                'updated_at' => '2025-06-18 11:00:00',
            ],
            [
                'batch_number' => 'NCE-202401-001',
                'manufacturing_date' => '2024-01-01',
                'expiry_date' => '2024-07-01',
                'supplier_id' => 2,
                'received_date' => '2024-01-10',
                'invoice_number' => 'INV-202401-001',
                'status' => 'expired',
                'notes' => 'Lô hàng cũ đã hết hạn và hết hàng.',
                'created_at' => '2024-01-10 09:00:00',
                'updated_at' => '2025-01-01 00:00:00',
            ],
            [
                'batch_number' => 'CFL-202505-001',
                'manufacturing_date' => '2025-05-10',
                'expiry_date' => '2026-05-10',
                'supplier_id' => 4,
                'received_date' => '2025-05-20',
                'invoice_number' => 'INV-202505-002',
                'status' => 'low_stock',
                'notes' => 'Lô hàng cà phê sắp hết.',
                'created_at' => '2025-05-20 10:00:00',
                'updated_at' => '2025-06-01 10:00:00',
            ],
            [
                'batch_number' => 'MXST-202505-001',
                'manufacturing_date' => '2025-05-10',
                'expiry_date' => null,
                'supplier_id' => 8,
                'received_date' => '2025-06-05',
                'invoice_number' => 'PNK-20250605-005',
                'status' => 'active',
                'notes' => 'Lô hàng nhập cho Máy xay sinh tố mini.',
                'created_at' => '2025-06-05 09:00:00',
                'updated_at' => '2025-06-05 09:00:00',
            ],
            [
                'batch_number' => 'CLN-202505-001',
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => null,
                'supplier_id' => 9,
                'received_date' => '2025-06-05',
                'invoice_number' => 'PNK-20250605-005',
                'status' => 'active',
                'notes' => 'Lô hàng nhập cho Chổi lau nhà 360 độ.',
                'created_at' => '2025-06-05 09:00:00',
                'updated_at' => '2025-06-05 09:00:00',
            ],
        ];

        foreach ($batches as $batch) {
            Batch::create($batch);
        }
    }
}