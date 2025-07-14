<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ProductTestSeeder extends Seeder
{
    public function run()
    {
        DB::table('products')->insert([
            [
                'name' => 'Sữa Ensure Gold',
                'stock' => 20,
                'price' => 50000,
                'expiry_date' => Carbon::now()->addDays(20),
                'max_stock' => 100,
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Mì Hảo Hảo',
                'stock' => 0,
                'price' => 3000,
                'expiry_date' => Carbon::now()->addDays(10),
                'max_stock' => 50,
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Coca Cola',
                'stock' => 120,
                'price' => 10000,
                'expiry_date' => Carbon::now()->addDays(100),
                'max_stock' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pepsi',
                'stock' => 80,
                'price' => 9000,
                'expiry_date' => Carbon::now()->addDays(90),
                'max_stock' => 60,
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Bánh Oreo',
                'stock' => 40,
                'price' => 15000,
                'expiry_date' => Carbon::now()->addDays(60),
                'max_stock' => 80,
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Sữa Milo',
                'stock' => 60,
                'price' => 20000,
                'expiry_date' => Carbon::now()->addDays(30),
                'max_stock' => 70,
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
} 