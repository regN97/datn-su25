<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductSupplier;

class ProductSupplierSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['product_id' => 1, 'supplier_id' => 1],
            ['product_id' => 2, 'supplier_id' => 2],
            ['product_id' => 3, 'supplier_id' => 3],
            ['product_id' => 4, 'supplier_id' => 4],
            ['product_id' => 5, 'supplier_id' => 5],
            ['product_id' => 6, 'supplier_id' => 6],
            ['product_id' => 7, 'supplier_id' => 7],
            ['product_id' => 8, 'supplier_id' => 8],
            ['product_id' => 9, 'supplier_id' => 9],
            ['product_id' => 10, 'supplier_id' => 10],
            ['product_id' => 1, 'supplier_id' => 11],
            ['product_id' => 2, 'supplier_id' => 12],
            ['product_id' => 3, 'supplier_id' => 13],
            ['product_id' => 4, 'supplier_id' => 14],
        ];

        foreach ($data as $item) {
            ProductSupplier::create($item);
        }
    }
}
