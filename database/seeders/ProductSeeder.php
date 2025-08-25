<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'name' => 'Bánh quy Cosy Marie 200g',
                'sku' => 'COS-001',
                'barcode' => '8936001234567',
                'description' => 'Bánh quy Cosy Marie vị bơ thơm ngon',
                'category_id' => 6,
                'unit_id' => 3, // Hộp
                'selling_price' => 25000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Kẹo dẻo Haribo Goldbears 100g',
                'sku' => 'HRB-001',
                'barcode' => '8936001234574',
                'description' => 'Kẹo dẻo Haribo hình gấu nhiều màu sắc',
                'category_id' => 6,
                'unit_id' => 2, // Gói
                'selling_price' => 30000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 2: Đồ ăn nhẹ
            [
                'name' => 'Snack Lay’s Vị Muối 70g',
                'sku' => 'LAY-001',
                'barcode' => '8936002345678',
                'description' => 'Snack khoai tây chiên Lay’s vị muối nguyên bản',
                'category_id' => 6,
                'unit_id' => 2, // Gói
                'selling_price' => 12000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Snack đùi gà 50g Phúc Thịnh',
                'sku' => 'PHUC-001',
                'barcode' => '8938533974517',
                'description' => 'Snack đùi gà Phúc Thịnh vị cay giòn rụm',
                'category_id' => 6,
                'unit_id' => 2, // Gói
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Snack Oishi Vị Tôm 50g',
                'sku' => 'OIS-001',
                'barcode' => '8936002345685',
                'description' => 'Snack Oishi vị tôm cay giòn rụm',
                'category_id' => 6,
                'unit_id' => 2, // Gói
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 3: Bánh kẹo
            [
                'name' => 'Kẹo Chupa Chups 12g',
                'sku' => 'CCP-001',
                'barcode' => '8936003456789',
                'description' => 'Kẹo mút Chupa Chups nhiều hương vị',
                'category_id' => 6,
                'unit_id' => 1, // Cái
                'selling_price' => 5000,
                'min_stock_level' => 200,
                'max_stock_level' => 2000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Bánh Chocopie Orion 330g',
                'sku' => 'ORI-001',
                'barcode' => '8936003456796',
                'description' => 'Bánh Chocopie Orion nhân sô-cô-la',
                'category_id' => 6,
                'unit_id' => 3, // Hộp
                'selling_price' => 45000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // ...existing code...

            // Danh mục 7: Gia vị
            [
                'name' => 'Nước mắm Phú Quốc 500ml',
                'sku' => 'NMP-001',
                'barcode' => '8936007890123',
                'description' => 'Nước mắm Phú Quốc nguyên chất, độ đạm cao',
                'category_id' => 7,
                'unit_id' => 1, // Chai
                'selling_price' => 45000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Muối ăn VISACO',
                'sku' => 'VIS-001',
                'barcode' => '8935002800445',
                'description' => 'Muối ăn VISACO tinh khiết, an toàn cho sức khỏe',
                'category_id' => 7,
                'unit_id' => 2, // Gói
                'selling_price' => 45000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Muối iod Tico 1kg',
                'sku' => 'MTC-001',
                'barcode' => '8936007890130',
                'description' => 'Muối iod Tico tinh khiết, dùng trong nấu ăn',
                'category_id' => 7,
                'unit_id' => 2, // Gói
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 8: Dầu ăn
            [
                'name' => 'Dầu ăn Tường An 1L',
                'sku' => 'TAN-001',
                'barcode' => '8936008901234',
                'description' => 'Dầu ăn Tường An tinh luyện, an toàn',
                'category_id' => 7,
                'unit_id' => 1, // Chai
                'selling_price' => 35000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Dầu ô liu Borges 500ml',
                'sku' => 'BGS-001',
                'barcode' => '8936008901241',
                'description' => 'Dầu ô liu nguyên chất, nhập khẩu từ Tây Ban Nha',
                'category_id' => 7,
                'unit_id' => 1, // Chai
                'selling_price' => 120000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 9: Đồ uống có cồn
            [
                'name' => 'Bia Heineken 330ml',
                'sku' => 'HNK-001',
                'barcode' => '8936009012345',
                'description' => 'Bia Heineken lon 330ml, nhập khẩu Hà Lan',
                'category_id' => 2,
                'unit_id' => 7, // Lon
                'selling_price' => 18000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Rượu vang đỏ Chile 750ml',
                'sku' => 'VGC-001',
                'barcode' => '8936009012352',
                'description' => 'Rượu vang đỏ Chile thơm ngon, nhập khẩu',
                'category_id' => 2,
                'unit_id' => 1, // Chai
                'selling_price' => 250000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 10: Sản phẩm chăm sóc cá nhân
            [
                'name' => 'Sữa tắm Dove 900ml',
                'sku' => 'DOV-001',
                'barcode' => '8936010123456',
                'description' => 'Sữa tắm Dove dưỡng ẩm, hương hoa hồng',
                'category_id' => 4,
                'unit_id' => 1, // Chai
                'selling_price' => 120000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Khẩu trang cao cấp 5D Mask gói 10 chiếc',
                'sku' => 'KT-001',
                'barcode' => '8938555760013',
                'description' => 'Khẩu trang cao cấp 5D Mask, bảo vệ sức khỏe',
                'category_id' => 4,
                'unit_id' => 2, // Gói
                'selling_price' => 10000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Dầu gội Head & Shoulders 650ml',
                'sku' => 'HNS-001',
                'barcode' => '8936010123463',
                'description' => 'Dầu gội Head & Shoulders sạch gàu',
                'category_id' => 4,
                'unit_id' => 1, // Chai
                'selling_price' => 90000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 11: Nước giải khát
            [
                'name' => 'Coca Cola Original 330ml',
                'sku' => 'CCL-001',
                'barcode' => '8935001234567',
                'description' => 'Nước giải khát có ga Coca Cola lon 330ml',
                'category_id' => 10,
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
                'category_id' => 10,
                'unit_id' => 7, // Lon
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 12: Nước suối & Nước khoáng
            [
                'name' => 'Nước khoáng LaVie 500ml',
                'sku' => 'LV-001',
                'barcode' => '8935005801029',
                'description' => 'Nước khoáng thiên nhiên LaVie',
                'category_id' => 11,
                'unit_id' => 1, // Chai
                'selling_price' => 5000,
                'min_stock_level' => 200,
                'max_stock_level' => 2000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Nước suối Aquafina 500ml',
                'sku' => 'AQF-001',
                'barcode' => '8935008901241',
                'description' => 'Nước suối tinh khiết Aquafina',
                'category_id' => 11,
                'unit_id' => 1, // Chai
                'selling_price' => 6000,
                'min_stock_level' => 200,
                'max_stock_level' => 2000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 13: Cà phê & Trà
            [
                'name' => 'Cà phê lon Birdy sữa 170ml',
                'sku' => 'BS-001',
                'barcode' => '8935039570700',
                'description' => 'Cà phê lon Birdy sữa 170ml',
                'category_id' => 12,
                'unit_id' => 7, // Hộp
                'selling_price' => 15000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Trà xanh C2 500ml',
                'sku' => 'C2T-001',
                'barcode' => '8935009012352',
                'description' => 'Trà xanh C2 vị tự nhiên, đóng chai tiện lợi',
                'category_id' => 12,
                'unit_id' => 1, // Chai
                'selling_price' => 8000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 14: Mì & Phở ăn liền
            [
                'name' => 'Mì Hảo Hảo Tôm Chua Cay 75g',
                'sku' => 'MHH-001',
                'barcode' => '8934563138165',
                'description' => 'Mì ăn liền Hảo Hảo hương vị tôm chua cay đặc trưng',
                'category_id' => 13,
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
                'category_id' => 13,
                'unit_id' => 3, // Hộp
                'selling_price' => 15000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
        ];
        foreach ($products as $product) {
            $product['stock_quantity'] = 0;

            $product['last_received_at'] = now()->subDays(rand(0, 30))->toDateString();

            $product['is_trackable'] = true;

            Product::create($product);
        }
    }
}
