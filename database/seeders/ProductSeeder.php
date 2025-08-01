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
                'name' => 'Kẹo dẻo Haribo Goldbears 100g',
                'sku' => 'HRB-001',
                'barcode' => '8936001234574',
                'description' => 'Kẹo dẻo Haribo hình gấu nhiều màu sắc',
                'category_id' => 1,
                'unit_id' => 2, // Gói
                'selling_price' => 30000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/product_images/HEEKFBrxy9efc1W00LcX352Wa3BfOdHhxfrklXC0.jpg'
            ],

            // Danh mục 2: Đồ ăn nhẹ
            [
                'name' => 'Snack Lay’s Vị Muối 70g',
                'sku' => 'LAY-001',
                'barcode' => '8936002345678',
                'description' => 'Snack khoai tây chiên Lay’s vị muối nguyên bản',
                'category_id' => 2,
                'unit_id' => 2, // Gói
                'selling_price' => 12000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/product_images/4Ue4zAP627CIUHjkMuWeISgDBaV5gtYif0uiKZjK.jpg'
            ],
            [
                'name' => 'Snack Oishi Vị Tôm 50g',
                'sku' => 'OIS-001',
                'barcode' => '8936002345685',
                'description' => 'Snack Oishi vị tôm cay giòn rụm',
                'category_id' => 2,
                'unit_id' => 2, // Gói
                'selling_price' => 10000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/product_images/U4TgPoOGKdFalurZYkf2eHUa6f4DKrgvjeN5hsm3.jpg'
            ],

            // Danh mục 3: Bánh kẹo
            [
                'name' => 'Kẹo Chupa Chups 12g',
                'sku' => 'CCP-001',
                'barcode' => '8936003456789',
                'description' => 'Kẹo mút Chupa Chups nhiều hương vị',
                'category_id' => 3,
                'unit_id' => 1, // Cái
                'selling_price' => 5000,
                'min_stock_level' => 200,
                'max_stock_level' => 2000,
                'is_active' => true,
                'image_url' => '/storage/product_images/OzQjLwWzsLcpz1oNQ4yQ3CKTCF55sFDfT74dk91k.jpg'
            ],
            [
                'name' => 'Bánh Chocopie Orion 330g',
                'sku' => 'ORI-001',
                'barcode' => '8936003456796',
                'description' => 'Bánh Chocopie Orion nhân sô-cô-la',
                'category_id' => 3,
                'unit_id' => 3, // Hộp
                'selling_price' => 45000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/product_images/6ySCGkjQmPxALXsQVcCoJXG9AokhPqGUZsDIQiry.jpg'
            ],

            // Danh mục 4: Thực phẩm đông lạnh
            [
                'name' => 'Xúc xích CP 500g',
                'sku' => 'CPC-001',
                'barcode' => '8936004567890',
                'description' => 'Xúc xích CP tươi ngon, tiện lợi',
                'category_id' => 4,
                'unit_id' => 2, // Gói
                'selling_price' => 55000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/product_images/DFBBzF7vit8r5HV87W4VBLRWXMCa6Y2pWvoZpwma.jpg'
            ],
            [
                'name' => 'Chả cá basa 300g',
                'sku' => 'CCB-001',
                'barcode' => '8936004567906',
                'description' => 'Chả cá basa đông lạnh, dùng cho lẩu hoặc chiên',
                'category_id' => 4,
                'unit_id' => 2, // Gói
                'selling_price' => 40000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/product_images/E0cvQez1zxqNoX4GzPwQXQMFr3jOlFvik1Tp7VLh.jpg'
            ],

            // Danh mục 5: Thực phẩm tươi sống
            [
                'name' => 'Thịt bò thăn 1kg',
                'sku' => 'TBT-001',
                'barcode' => '8936005678901',
                'description' => 'Thịt bò thăn tươi, chất lượng cao',
                'category_id' => 5,
                'unit_id' => 4, // Kg
                'selling_price' => 250000,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'is_active' => true,
                'image_url' => '/storage/product_images/1GcIy3VJ9GDCBsoWS1nvUamEomEbNCZ8pUcv7ur7.jpg'
            ],
            [
                'name' => 'Cá hồi phi lê 500g',
                'sku' => 'CHO-001',
                'barcode' => '8936005678918',
                'description' => 'Cá hồi phi lê tươi, nhập khẩu từ Na Uy',
                'category_id' => 5,
                'unit_id' => 4, // Kg
                'selling_price' => 350000,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'is_active' => true,
                'image_url' => '/storage/product_images/Bziot9fVb6Yu5nauNen7vwBJVEIwuJcCQswaJtil.jpg'
            ],

            // Danh mục 6: Rau củ quả
            [
                'name' => 'Cà chua 1kg',
                'sku' => 'CCH-001',
                'barcode' => '8936006789012',
                'description' => 'Cà chua tươi sạch, nguồn gốc Đà Lạt',
                'category_id' => 6,
                'unit_id' => 4, // Kg
                'selling_price' => 25000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/product_images/XLZno0ZsKVS61W4f3s5tPrSTyl8PCEfV2kK52HXB.jpg'
            ],
            [
                'name' => 'Khoai tây Đà Lạt 1kg',
                'sku' => 'KTD-001',
                'barcode' => '8936006789029',
                'description' => 'Khoai tây sạch, dùng cho chiên hoặc luộc',
                'category_id' => 6,
                'unit_id' => 4, // Kg
                'selling_price' => 30000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/product_images/pj4HJJN9J2WxDlPK8jUfB9FtWudbBTGkoZR8a1sR.jpg'
            ],

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
                'image_url' => '/storage/product_images/ZAj7vy49nYG2CgiLBfHQUclH0gyRLNHXGbRZBgbS.jpg'
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
                'image_url' => '/storage/product_images/DRtuOcbOcgCBh1ZLHg76oshd2stIJdhqVWXMKx4m.jpg'
            ],

            // Danh mục 8: Dầu ăn
            [
                'name' => 'Dầu ăn Tường An 1L',
                'sku' => 'TAN-001',
                'barcode' => '8936008901234',
                'description' => 'Dầu ăn Tường An tinh luyện, an toàn',
                'category_id' => 8,
                'unit_id' => 1, // Chai
                'selling_price' => 35000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/product_images/1eNU7afW3b2uXXjLjNBxGD6emrSyGtt6vP9j30qo.jpg'
            ],
            [
                'name' => 'Dầu ô liu Borges 500ml',
                'sku' => 'BGS-001',
                'barcode' => '8936008901241',
                'description' => 'Dầu ô liu nguyên chất, nhập khẩu từ Tây Ban Nha',
                'category_id' => 8,
                'unit_id' => 1, // Chai
                'selling_price' => 120000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/product_images/E67YKH0e71wYhvhKJVrtR9LvhadXJDS4nCv8bm30.jpg'
            ],

            // Danh mục 9: Đồ uống có cồn
            [
                'name' => 'Bia Heineken 330ml',
                'sku' => 'HNK-001',
                'barcode' => '8936009012345',
                'description' => 'Bia Heineken lon 330ml, nhập khẩu Hà Lan',
                'category_id' => 9,
                'unit_id' => 7, // Lon
                'selling_price' => 18000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'is_active' => true,
                'image_url' => '/storage/product_images/PWmVKhI8O8LmqsXNS0Wd2TfgUADZD40RJh0C2if0.jpg'
            ],
            [
                'name' => 'Rượu vang đỏ Chile 750ml',
                'sku' => 'VGC-001',
                'barcode' => '8936009012352',
                'description' => 'Rượu vang đỏ Chile thơm ngon, nhập khẩu',
                'category_id' => 9,
                'unit_id' => 1, // Chai
                'selling_price' => 250000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/product_images/iF6jLG2gg0J1JYGGyC8lpX2ayBHO22nHXVOVf9Mf.jpg'
            ],

            // Danh mục 10: Sản phẩm chăm sóc cá nhân
            [
                'name' => 'Sữa tắm Dove 900ml',
                'sku' => 'DOV-001',
                'barcode' => '8936010123456',
                'description' => 'Sữa tắm Dove dưỡng ẩm, hương hoa hồng',
                'category_id' => 10,
                'unit_id' => 1, // Chai
                'selling_price' => 120000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/product_images/epv34MoIDlcJSzC1osTZ45Zim0lSf9Lku2rPfEn1.jpg'
            ],
            [
                'name' => 'Dầu gội Head & Shoulders 650ml',
                'sku' => 'HNS-001',
                'barcode' => '8936010123463',
                'description' => 'Dầu gội Head & Shoulders sạch gàu',
                'category_id' => 10,
                'unit_id' => 1, // Chai
                'selling_price' => 90000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/product_images/O1BQrhuT1CRd5zCoPkChyVrsx5XUK5pBw9HF78HC.jpg'
            ],

            // Danh mục 11: Nước giải khát
            
        ];
        foreach ($products as $product) {
            $product['stock_quantity'] = rand($product['min_stock_level'], $product['max_stock_level']);

            $product['last_received_at'] = now()->subDays(rand(0, 30))->toDateString();
            $product['last_sold_at'] = rand(0, 1) ? now()->subDays(rand(0, 15))->toDateString() : null;

            $product['is_trackable'] = true;
            $product['reorder_point'] = intval($product['min_stock_level'] * rand(70, 90) / 100);
            Product::create($product);
        }
    }
}
