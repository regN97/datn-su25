<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BillDetail;

class BillDetailSeeder extends Seeder
{
    public function run(): void
    {
        $billDetails = [
            // Chi tiết cho BILL-0001
            [
                'bill_id'           => 1,
                'product_id'        => 1,
                'batch_id'          => 1,
                'p_name'            => 'Bánh quy Cosy Marie 200g',
                'p_sku'             => 'COS-001',
                'p_barcode'         => '8936001234567',
                'quantity'          => 2,
                'unit_cost'         => 10000,
                'unit_price'        => 25000,
                'discount_per_item' => 0,
                'subtotal'          => 50000,
            ],
            [
                'bill_id'           => 1,
                'product_id'        => 2,
                'batch_id'          => 1,
                'p_name'            => 'Kẹo dẻo Haribo Goldbears 100g',
                'p_sku'             => 'HRB-001',
                'p_barcode'         => '8936001234574',
                'quantity'          => 3,
                'unit_cost'         => 15000,
                'unit_price'        => 30000,
                'discount_per_item' => 1000,
                'subtotal'          => 87000,
            ],

            // Chi tiết cho BILL-0002
            [
                'bill_id'           => 2,
                'product_id'        => 3,
                'batch_id'          => 2,
                'p_name'            => 'Snack Lay’s Vị Muối 70g',
                'p_sku'             => 'LAY-001',
                'p_barcode'         => '8936002345678',
                'quantity'          => 5,
                'unit_cost'         => 8000,
                'unit_price'        => 12000,
                'discount_per_item' => 0,
                'subtotal'          => 60000,
            ],
            [
                'bill_id'           => 2,
                'product_id'        => 4,
                'batch_id'          => 2,
                'p_name'            => 'Snack Oishi Vị Tôm 50g',
                'p_sku'             => 'OIS-001',
                'p_barcode'         => '8936002345685',
                'quantity'          => 4,
                'unit_cost'         => 7000,
                'unit_price'        => 10000,
                'discount_per_item' => 500,
                'subtotal'          => 38000,
            ],

        ];

        foreach ($billDetails as $detail) {
            BillDetail::create($detail);
        }
    }
}
