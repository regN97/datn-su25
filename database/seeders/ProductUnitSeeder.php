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
                'description' => 'Đơn vị cho nước giải khát, dầu ăn, nước mắm...',
            ],
            [
                'name' => 'Gói',
                'description' => 'Đơn vị cho mì gói, bánh kẹo, snack...',
            ],
            [
                'name' => 'Hộp',
                'description' => 'Đơn vị cho sữa, thực phẩm đóng hộp, TPCN...',
            ],
            [
                'name' => 'Kg',
                'description' => 'Đơn vị cho gạo, đường, bột...',
            ],
            [
                'name' => 'Lốc',
                'description' => 'Đơn vị cho combo nhiều chai/lon (6 chai)...',
            ],
            [
                'name' => 'Thùng',
                'description' => 'Đơn vị cho số lượng lớn (24 chai/lon)...',
            ],
            [
                'name' => 'Lon',
                'description' => 'Đơn vị cho nước giải khát, sữa đóng lon...',
            ],
            [
                'name' => 'Túi',
                'description' => 'Đơn vị cho bánh snack, đồ khô...',
            ],
            [
                'name' => 'Cái',
                'description' => 'Đơn vị cho thiết bị, dụng cụ...',
            ],
            [
                'name' => 'Gram',
                'description' => 'Đơn vị cho gia vị, thực phẩm định lượng nhỏ...',
            ],
        ];

        foreach ($unitsData as $unit) {
            ProductUnit::create($unit);
        }
    }
}
