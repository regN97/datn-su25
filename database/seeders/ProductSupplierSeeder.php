<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductSupplier;

class ProductSupplierSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['product_id' => 1, 'supplier_id' => 1, 'purchase_price' => 3500],
            ['product_id' => 2, 'supplier_id' => 2, 'purchase_price' => 12000],
            ['product_id' => 3, 'supplier_id' => 3, 'purchase_price' => 7000],
            ['product_id' => 4, 'supplier_id' => 4, 'purchase_price' => 7000],
            ['product_id' => 5, 'supplier_id' => 5, 'purchase_price' => 620000],
            ['product_id' => 6, 'supplier_id' => 6, 'purchase_price' => 180000],
            ['product_id' => 7, 'supplier_id' => 7, 'purchase_price' => 150000],
            ['product_id' => 8, 'supplier_id' => 8, 'purchase_price' => 25000],
            ['product_id' => 9, 'supplier_id' => 9, 'purchase_price' => 240000],
            ['product_id' => 10, 'supplier_id' => 10, 'purchase_price' => 4000],
            ['product_id' => 11, 'supplier_id' => 10, 'purchase_price' => 50000],
            ['product_id' => 1, 'supplier_id' => 7, 'purchase_price' => 4000],
            ['product_id' => 2, 'supplier_id' => 8, 'purchase_price' => 13000],
            ['product_id' => 3, 'supplier_id' => 9, 'purchase_price' => 8000],
            ['product_id' => 4, 'supplier_id' => 10, 'purchase_price' => 8000],
        ];

        foreach ($data as $item) {
            ProductSupplier::create($item);
        }
    }
}
