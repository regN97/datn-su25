<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            // Mì & Phở ăn liền (category_id: 14)
            [
                'name' => 'Mì Hảo Hảo Tôm Chua Cay 75g',
                'sku' => 'MHH-001',
                'barcode' => '8934563138165',
                'description' => 'Mì ăn liền Hảo Hảo hương vị tôm chua cay đặc trưng',
                'category_id' => 14,
                'unit_id' => 2, // Gói
                'selling_price' => 4000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Phở Gà Vifon Bowl 120g',
                'sku' => 'PVF-001',
                'barcode' => '8934563212458',
                'description' => 'Phở ăn liền hương vị gà, kèm gói gia vị và rau thơm',
                'category_id' => 14,
                'unit_id' => 3, // Hộp
                'selling_price' => 15000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Nước giải khát (category_id: 11)
            [
                'name' => 'Coca Cola Original 330ml',
                'sku' => 'CCL-001',
                'barcode' => '8935001234567',
                'description' => 'Nước giải khát có ga Coca Cola lon 330ml',
                'category_id' => 11,
                'unit_id' => 7, // Lon
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Pepsi Black Lime 330ml',
                'sku' => 'PBL-001',
                'barcode' => '8935002345678',
                'description' => 'Nước giải khát có ga Pepsi không đường hương chanh lon 330ml',
                'category_id' => 11,
                'unit_id' => 7, // Lon
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Sữa bột & Dinh dưỡng (category_id: 17)
            [
                'name' => 'Sữa Ensure Gold 850g',
                'sku' => 'EG-001',
                'barcode' => '8935003456789',
                'description' => 'Sữa bột dinh dưỡng Ensure Gold dành cho người lớn tuổi',
                'category_id' => 17,
                'unit_id' => 3, // Hộp
                'selling_price' => 675000,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Vitamin & TPCN (category_id: 20)
            [
                'name' => 'Centrum Adults 30 viên',
                'sku' => 'CTA-001',
                'barcode' => '8935004567890',
                'description' => 'Vitamin tổng hợp cho người trưởng thành',
                'category_id' => 20,
                'unit_id' => 3, // Hộp
                'selling_price' => 220000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Gạo & Ngũ cốc (category_id: 16)
            [
                'name' => 'Gạo ST25 5kg',
                'sku' => 'ST25-001',
                'barcode' => '8935005678901',
                'description' => 'Gạo ST25 - Gạo ngon nhất thế giới năm 2019',
                'category_id' => 16,
                'unit_id' => 4, // Kg
                'selling_price' => 180000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Đồ hộp & Chế biến (category_id: 15)
            [
                'name' => 'Cá hộp 3 Cô Gái 180g',
                'sku' => '3CG-001',
                'barcode' => '8935006789012',
                'description' => 'Cá thu sốt cà chua đóng hộp',
                'category_id' => 15,
                'unit_id' => 3, // Hộp
                'selling_price' => 32000,
                'min_stock_level' => 50,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Tã & Khăn ướt (category_id: 18)
            [
                'name' => 'Tã Bobby Fresh L54',
                'sku' => 'BBF-001',
                'barcode' => '8935007890123',
                'description' => 'Tã quần Bobby Fresh size L 54 miếng',
                'category_id' => 18,
                'unit_id' => 3, // Hộp
                'selling_price' => 280000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Nước suối & Nước khoáng (category_id: 12)
            [
                'name' => 'Nước khoáng LaVie 500ml',
                'sku' => 'LV-001',
                'barcode' => '8935008901234',
                'description' => 'Nước khoáng thiên nhiên LaVie',
                'category_id' => 12,
                'unit_id' => 1, // Chai
                'selling_price' => 5000,
                'min_stock_level' => 200,
                'max_stock_level' => 2000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Cà phê & Trà (category_id: 13)
            [
                'name' => 'Cà phê Trung Nguyên Sáng tạo 2',
                'sku' => 'TNST2-001',
                'barcode' => '8935009012345',
                'description' => 'Cà phê bột Trung Nguyên Sáng tạo 2 340g',
                'category_id' => 13,
                'unit_id' => 3, // Hộp
                'selling_price' => 65000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
