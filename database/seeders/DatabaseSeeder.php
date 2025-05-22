<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\ProductUnitSeeder;
use Database\Seeders\ProductSupplierSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SupplierSeeder::class,
            CategorySeeder::class,
            ProductUnitSeeder::class,
            ProductSeeder::class,
            ProductSupplierSeeder::class,

        ]);
    }
}
