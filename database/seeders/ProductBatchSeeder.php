<?php

namespace Database\Seeders;

use App\Models\ProductBatch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductBatchSeeder extends Seeder
{
    public function run(): void
    {
        $product_batches = [
            [
                'product_id' => 5,
                'batch_id' => 1, // Reference to batches table
                'purchase_price' => 180000,
                'initial_quantity' => 9,
                'current_quantity' => 9,
            ],
            [
                'product_id' => 7,
                'batch_id' => 2,
                'purchase_price' => 45000,
                'initial_quantity' => 20, 
                'current_quantity' => 20,
            ],
            [
                'product_id' => 8,
                'batch_id' => 3,
                'purchase_price' => 20000,
                'initial_quantity' => 15,
                'current_quantity' => 15,
            ],
            [
                'product_id' => 10,
                'batch_id' => 4,
                'purchase_price' => 25000,
                'initial_quantity' => 60,
                'current_quantity' => 60,
            ],
            [
                'product_id' => 1,
                'batch_id' => 5,
                'purchase_price' => 5000,
                'initial_quantity' => 18,
                'current_quantity' => 18,
            ],
            [
                'product_id' => 5,
                'batch_id' => 6,
                'purchase_price' => 180000,
                'initial_quantity' => 5,
                'current_quantity' => 5,
            ],
            [
                'product_id' => 2,
                'batch_id' => 7,
                'purchase_price' => 20000,
                'initial_quantity' => 25,
                'current_quantity' => 25,
            ],
            [
                'product_id' => 3,
                'batch_id' => 8,
                'purchase_price' => 7000,
                'initial_quantity' => 100,
                'current_quantity' => 0,
            ],
            [
                'product_id' => 4,
                'batch_id' => 9,
                'purchase_price' => 9000,
                'initial_quantity' => 50,
                'current_quantity' => 5,
            ],
            [
                'product_id' => 6,
                'batch_id' => 10,
                'purchase_price' => 220000,
                'initial_quantity' => 12,
                'current_quantity' => 12,
            ],
            [
                'product_id' => 9,
                'batch_id' => 11,
                'purchase_price' => 100000,
                'initial_quantity' => 25,
                'current_quantity' => 25,
            ],
        ];

        foreach ($product_batches as $product_batch) {
            ProductBatch::create($product_batch);
        }
    }
}