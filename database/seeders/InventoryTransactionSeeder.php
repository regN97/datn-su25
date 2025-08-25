<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use Illuminate\Database\Seeder;

class InventoryTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions = [
            // Giao dịch từ Batch 1 (REC-20250801-201) - Nhập hàng
            [
                'transaction_type_id' => 1, // Stock In - Nhập kho
                'product_id' => 18, // Coca Cola Original 330ml
                'quantity_change' => 1000,
                'stock_after' => 1000,
                'unit_price' => 7000,
                'total_value' => 7000000,
                'transaction_date' => '2025-08-15 09:15:00',
                'related_batch_id' => 1,
                'related_bill_id' => null,
                'related_purchase_return_id' => null,
                'user_id' => 1,
                'note' => 'Nhập kho từ lô hàng REC-20250801-201',
                'created_at' => '2025-08-15 09:15:00',
                'updated_at' => '2025-08-15 09:15:00',
            ],
            [
                'transaction_type_id' => 1, // Stock In - Nhập kho
                'product_id' => 19, // Pepsi Black Lime 330ml
                'quantity_change' => 1000,
                'stock_after' => 1000,
                'unit_price' => 7000,
                'total_value' => 7000000,
                'transaction_date' => '2025-08-15 09:15:00',
                'related_batch_id' => 1,
                'related_bill_id' => null,
                'related_purchase_return_id' => null,
                'user_id' => 1,
                'note' => 'Nhập kho từ lô hàng REC-20250801-201',
                'created_at' => '2025-08-15 09:15:00',
                'updated_at' => '2025-08-15 09:15:00',
            ],

            // Giao dịch từ Batch 2 (REC-20250810-202) - Nhập hàng
            [
                'transaction_type_id' => 1, // Stock In - Nhập kho
                'product_id' => 20, // Nước khoáng LaVie
                'quantity_change' => 2000,
                'stock_after' => 2000,
                'unit_price' => 4000,
                'total_value' => 8000000,
                'transaction_date' => '2025-08-22 16:45:00',
                'related_batch_id' => 2,
                'related_bill_id' => null,
                'related_purchase_return_id' => null,
                'user_id' => 1,
                'note' => 'Nhập kho từ lô hàng REC-20250810-202',
                'created_at' => '2025-08-22 16:45:00',
                'updated_at' => '2025-08-22 16:45:00',
            ],
            [
                'transaction_type_id' => 1, // Stock In - Nhập kho
                'product_id' => 22, // Cà phê lon Birdy sữa
                'quantity_change' => 500,
                'stock_after' => 500,
                'unit_price' => 10000,
                'total_value' => 5000000,
                'transaction_date' => '2025-08-22 16:45:00',
                'related_batch_id' => 2,
                'related_bill_id' => null,
                'related_purchase_return_id' => null,
                'user_id' => 1,
                'note' => 'Nhập kho từ lô hàng REC-20250810-202',
                'created_at' => '2025-08-22 16:45:00',
                'updated_at' => '2025-08-22 16:45:00',
            ],

            // Giao dịch từ Bill - Bán hàng (BILL-0001)
            [
                'transaction_type_id' => 2, // Stock Out - Xuất kho bán hàng
                'product_id' => 1, // Bánh quy Cosy Marie 200g
                'quantity_change' => -2, // Số âm cho xuất kho
                'stock_after' => 0, // Giả định stock ban đầu là 2
                'unit_price' => 25000,
                'total_value' => 50000,
                'transaction_date' => now(),
                'related_batch_id' => null,
                'related_bill_id' => 1,
                'related_purchase_return_id' => null,
                'user_id' => 1,
                'note' => 'Xuất kho bán hàng - Hóa đơn BILL-0001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaction_type_id' => 2, // Stock Out - Xuất kho bán hàng
                'product_id' => 2, // Kẹo dẻo Haribo
                'quantity_change' => -3, // Số âm cho xuất kho
                'stock_after' => 0, // Giả định stock ban đầu là 3
                'unit_price' => 30000,
                'total_value' => 87000,
                'transaction_date' => now(),
                'related_batch_id' => null,
                'related_bill_id' => 1,
                'related_purchase_return_id' => null,
                'user_id' => 1,
                'note' => 'Xuất kho bán hàng - Hóa đơn BILL-0001',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Giao dịch từ Purchase Return - Trả hàng nhà cung cấp (RET-20250602-001)
            [
                'transaction_type_id' => 3, // Stock Return - Trả hàng nhà cung cấp
                'product_id' => 18, // Coca Cola Original 330ml
                'quantity_change' => -200, // Số âm cho trả hàng
                'stock_after' => 800, // 1000 - 200
                'unit_price' => 7000,
                'total_value' => 1400000,
                'transaction_date' => '2025-08-16 10:30:00',
                'related_batch_id' => 1,
                'related_bill_id' => null,
                'related_purchase_return_id' => 1,
                'user_id' => 1,
                'note' => 'Trả hàng nhà cung cấp - Phiếu trả RET-20250602-001',
                'created_at' => '2025-08-16 10:30:00',
                'updated_at' => '2025-08-16 10:30:00',
            ],
            [
                'transaction_type_id' => 3, // Stock Return - Trả hàng nhà cung cấp
                'product_id' => 19, // Pepsi Black Lime 330ml
                'quantity_change' => -150, // Số âm cho trả hàng
                'stock_after' => 850, // 1000 - 150
                'unit_price' => 7000,
                'total_value' => 1050000,
                'transaction_date' => '2025-08-16 10:30:00',
                'related_batch_id' => 1,
                'related_bill_id' => null,
                'related_purchase_return_id' => 1,
                'user_id' => 1,
                'note' => 'Trả hàng nhà cung cấp - Phiếu trả RET-20250602-001',
                'created_at' => '2025-08-16 10:30:00',
                'updated_at' => '2025-08-16 10:30:00',
            ],
        ];

        foreach ($transactions as $transaction) {
            InventoryTransaction::create($transaction);
        }
    }
}
