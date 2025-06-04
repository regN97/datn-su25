<?php

namespace Database\Seeders;

use App\Models\ProductBatch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product_batches = [
            // Dữ liệu từ Purchase Receipt Item 1 (Bình đun siêu tốc - PRN-20250601-001)
            [
                'product_id' => 5, // Bình đun siêu tốc 1.8L
                'batch_number' => 'BDST-202505-001',
                'manufacturing_date' => '2025-05-01',
                'expiry_date' => null,
                'purchase_price' => 180000,
                'initial_quantity' => 9, // quantity_received từ purchase_receipt_items
                'current_quantity' => 9,
                'status' => 'active',
                'supplier_id' => 2, // Supplier của PO-20240601-002
                'received_date' => '2025-06-08', // receipt_date của PRN-20250601-001
                'invoice_number' => 'PNK-20250601-001', // receipt_number của PRN-20250601-001
                'notes' => 'Lô hàng nhập từ PO-20240601-002',
                'created_at' => '2025-06-08 10:00:00',
                'updated_at' => '2025-06-08 10:00:00',
            ],
            // Dữ liệu từ Purchase Receipt Item 2 (Sữa tắm - PRN-20250602-002)
            [
                'product_id' => 7, // Sữa tắm hương hoa 800ml
                'batch_number' => 'STHH-202504-002',
                'manufacturing_date' => '2025-04-10',
                'expiry_date' => '2027-04-10',
                'purchase_price' => 45000,
                'initial_quantity' => 20,
                'current_quantity' => 20,
                'status' => 'active',
                'supplier_id' => 4, // Supplier của PO-20240603-004
                'received_date' => '2025-06-09',
                'invoice_number' => 'PNK-20250602-002',
                'notes' => 'Lô hàng nhập từ PO-20240603-004',
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],
            // Dữ liệu từ Purchase Receipt Item 3 (Kem đánh răng - PRN-20250602-002)
            [
                'product_id' => 8, // Kem đánh răng dược liệu 150g
                'batch_number' => 'KDTRL-202503-003',
                'manufacturing_date' => '2025-03-05',
                'expiry_date' => '2026-03-05',
                'purchase_price' => 20000,
                'initial_quantity' => 15,
                'current_quantity' => 15,
                'status' => 'active',
                'supplier_id' => 4, // Supplier của PO-20240603-004
                'received_date' => '2025-06-09',
                'invoice_number' => 'PNK-20250602-002',
                'notes' => 'Lô hàng nhập từ PO-20240603-004',
                'created_at' => '2025-06-09 11:00:00',
                'updated_at' => '2025-06-09 11:00:00',
            ],
            // Dữ liệu từ Purchase Receipt Item 4 (Giẻ lau - PRN-20250603-003)
            [
                'product_id' => 10, // Giẻ lau đa năng 3 cái/gói
                'batch_number' => 'GLDN-202505-004',
                'manufacturing_date' => '2025-05-15',
                'expiry_date' => null,
                'purchase_price' => 25000,
                'initial_quantity' => 60,
                'current_quantity' => 60,
                'status' => 'active',
                'supplier_id' => 5, // Supplier của PO-20240605-006
                'received_date' => '2025-06-11',
                'invoice_number' => 'PNK-20250603-003',
                'notes' => 'Lô hàng nhập từ PO-20240605-006',
                'created_at' => '2025-06-11 12:00:00',
                'updated_at' => '2025-06-11 12:00:00',
            ],
            // Dữ liệu từ Purchase Receipt Item 5 (Mì ly - PRN-20250604-004)
            [
                'product_id' => 1, // Mì ly hảo hạng 65g
                'batch_number' => 'MLH-202505-005',
                'manufacturing_date' => '2025-05-20',
                'expiry_date' => '2025-11-20', // Sắp hết hạn
                'purchase_price' => 5000,
                'initial_quantity' => 18,
                'current_quantity' => 18,
                'status' => 'active', // Hoặc 'low_stock' nếu số lượng thấp
                'supplier_id' => 6, // Supplier của PO-20240606-007
                'received_date' => '2025-06-15',
                'invoice_number' => 'PNK-20250604-004',
                'notes' => 'Lô hàng nhập từ PO-20240606-007 (nhận thiếu)',
                'created_at' => '2025-06-15 09:00:00',
                'updated_at' => '2025-06-15 09:00:00',
            ],
            // Dữ liệu từ Purchase Receipt Item 6 (Bình đun siêu tốc - PRN-20250605-005)
            [
                'product_id' => 5, // Bình đun siêu tốc 1.8L
                'batch_number' => 'BDST-202506-006',
                'manufacturing_date' => '2025-06-01',
                'expiry_date' => null,
                'purchase_price' => 180000,
                'initial_quantity' => 5,
                'current_quantity' => 5,
                'status' => 'active',
                'supplier_id' => 8, // Supplier của PO-20240608-009
                'received_date' => '2025-06-16',
                'invoice_number' => 'PNK-20250605-005',
                'notes' => 'Lô hàng nhập từ PO-20240608-009',
                'created_at' => '2025-06-16 10:00:00',
                'updated_at' => '2025-06-16 10:00:00',
            ],
            // Dữ liệu từ Purchase Receipt Item 7 (Bánh quy bơ - PRN-20250606-006)
            [
                'product_id' => 2, // Bánh quy bơ hộp 200g
                'batch_number' => 'BQB-202506-007',
                'manufacturing_date' => '2025-06-05',
                'expiry_date' => '2026-06-05',
                'purchase_price' => 20000,
                'initial_quantity' => 25,
                'current_quantity' => 25,
                'status' => 'active',
                'supplier_id' => 1, // Supplier của PO-20240611-012
                'received_date' => '2025-06-18',
                'invoice_number' => 'PNK-20250606-006',
                'notes' => 'Lô hàng nhập từ PO-20240611-012',
                'created_at' => '2025-06-18 11:00:00',
                'updated_at' => '2025-06-18 11:00:00',
            ],
            // Thêm một lô hàng đã hết hạn để kiểm tra trạng thái 'expired'
            [
                'product_id' => 3, // Nước cam ép 330ml
                'batch_number' => 'NCE-202401-001',
                'manufacturing_date' => '2024-01-01',
                'expiry_date' => '2024-07-01', // Đã hết hạn
                'purchase_price' => 7000,
                'initial_quantity' => 100,
                'current_quantity' => 0, // Đã hết hàng
                'status' => 'expired',
                'supplier_id' => 2,
                'received_date' => '2024-01-10',
                'invoice_number' => 'INV-202401-001',
                'notes' => 'Lô hàng cũ đã hết hạn và hết hàng.',
                'created_at' => '2024-01-10 09:00:00',
                'updated_at' => '2025-01-01 00:00:00',
            ],
            // Thêm một lô hàng sắp hết hàng để kiểm tra trạng thái 'low_stock'
            [
                'product_id' => 4, // Cà phê lon đen đá 240ml
                'batch_number' => 'CFL-202505-001',
                'manufacturing_date' => '2025-05-10',
                'expiry_date' => '2026-05-10',
                'purchase_price' => 9000,
                'initial_quantity' => 50,
                'current_quantity' => 5, // Sắp hết hàng
                'status' => 'low_stock',
                'supplier_id' => 4,
                'received_date' => '2025-05-20',
                'invoice_number' => 'INV-202505-002',
                'notes' => 'Lô hàng cà phê sắp hết.',
                'created_at' => '2025-05-20 10:00:00',
                'updated_at' => '2025-06-01 10:00:00',
            ],
        ];
        foreach ($product_batches as $product_batch) {
            ProductBatch::create($product_batch);
        }
    }
}
