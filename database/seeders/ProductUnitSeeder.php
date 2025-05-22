<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductUnit;  // nhớ import model
use Illuminate\Support\Carbon;

class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $unitsData = [
            [
                'name' => 'Chai',
                'description' => 'Dùng cho sản phẩm nước uống, dầu ăn...',
            ],
            [
                'name' => 'Gói',
                'description' => 'Dùng cho mì tôm, bánh kẹo...',
            ],
            [
                'name' => 'Hộp',
                'description' => 'Dùng cho sữa, thực phẩm đóng hộp...',
            ],
            [
                'name' => 'Kg',
                'description' => 'Dùng cho thực phẩm tươi sống, gạo...',
            ],
            [
                'name' => 'Lốc',
                'description' => 'Dùng cho nước ngọt dạng lốc 6 chai...',
            ],
        ];

        foreach ($unitsData as $unit) {
            ProductUnit::create($unit);
        }
    }
}
