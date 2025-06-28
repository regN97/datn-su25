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
                'category_id' => 1,
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
                'category_id' => 1,
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
                'category_id' => 2,
                'unit_id' => 2, // Gói
                'selling_price' => 12000,
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
                'category_id' => 2,
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
                'category_id' => 3,
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
                'category_id' => 3,
                'unit_id' => 3, // Hộp
                'selling_price' => 45000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'image_url' => '/storage/piclumen-1747750187180.png'
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
                'category_id' => 8,
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
                'category_id' => 8,
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
                'category_id' => 9,
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
                'category_id' => 9,
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
                'category_id' => 10,
                'unit_id' => 1, // Chai
                'selling_price' => 120000,
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
                'category_id' => 10,
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

            // Danh mục 12: Nước suối & Nước khoáng
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
            [
                'name' => 'Nước suối Aquafina 500ml',
                'sku' => 'AQF-001',
                'barcode' => '8935008901241',
                'description' => 'Nước suối tinh khiết Aquafina',
                'category_id' => 12,
                'unit_id' => 1, // Chai
                'selling_price' => 6000,
                'min_stock_level' => 200,
                'max_stock_level' => 2000,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 13: Cà phê & Trà
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
            [
                'name' => 'Trà xanh C2 500ml',
                'sku' => 'C2T-001',
                'barcode' => '8935009012352',
                'description' => 'Trà xanh C2 vị tự nhiên, đóng chai tiện lợi',
                'category_id' => 13,
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

            // Danh mục 15: Đồ hộp & Chế biến
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
            [
                'name' => 'Thịt hộp Spam 340g',
                'sku' => 'SPM-001',
                'barcode' => '8935006789029',
                'description' => 'Thịt hộp Spam nhập khẩu, tiện lợi',
                'category_id' => 15,
                'unit_id' => 3, // Hộp
                'selling_price' => 75000,
                'min_stock_level' => 50,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 16: Gạo & Ngũ cốc
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
            [
                'name' => 'Yến mạch Quaker 1kg',
                'sku' => 'QKR-001',
                'barcode' => '8935005678918',
                'description' => 'Yến mạch Quaker nguyên cám, tốt cho sức khỏe',
                'category_id' => 16,
                'unit_id' => 2, // Gói
                'selling_price' => 65000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 17: Sữa bột & Dinh dưỡng
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
            [
                'name' => 'Sữa Pediasure 900g',
                'sku' => 'PDS-001',
                'barcode' => '8935003456796',
                'description' => 'Sữa bột Pediasure hỗ trợ phát triển trẻ em',
                'category_id' => 17,
                'unit_id' => 3, // Hộp
                'selling_price' => 720000,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 18: Tã & Khăn ướt
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
            [
                'name' => 'Khăn ướt Huggies 80 miếng',
                'sku' => 'HGS-001',
                'barcode' => '8935007890130',
                'description' => 'Khăn ướt Huggies an toàn cho da bé',
                'category_id' => 18,
                'unit_id' => 2, // Gói
                'selling_price' => 35000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 19: Sản phẩm vệ sinh
            [
                'name' => 'Nước rửa chén Sunlight 1L',
                'sku' => 'SNL-001',
                'barcode' => '8936011234567',
                'description' => 'Nước rửa chén Sunlight hương chanh sạch bóng',
                'category_id' => 19,
                'unit_id' => 1, // Chai
                'selling_price' => 40000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Bột giặt Omo 3kg',
                'sku' => 'OMO-001',
                'barcode' => '8936011234574',
                'description' => 'Bột giặt Omo sạch sâu, thơm lâu',
                'category_id' => 19,
                'unit_id' => 2, // Gói
                'selling_price' => 110000,
                'min_stock_level' => 30,
                'max_stock_level' => 300,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 20: Vitamin & TPCN
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
            [
                'name' => 'Dầu cá Omega-3 Fish Oil 100 viên',
                'sku' => 'FO3-001',
                'barcode' => '8935004567906',
                'description' => 'Dầu cá Omega-3 hỗ trợ sức khỏe tim mạch',
                'category_id' => 20,
                'unit_id' => 3, // Hộp
                'selling_price' => 180000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 21: Đồ dùng gia đình
            [
                'name' => 'Chổi quét nhà Lock&Lock',
                'sku' => 'LNL-001',
                'barcode' => '8936012345678',
                'description' => 'Chổi quét nhà Lock&Lock bền, tiện dụng',
                'category_id' => 21,
                'unit_id' => 1, // Cái
                'selling_price' => 65000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Thùng rác nhựa 20L',
                'sku' => 'TRC-001',
                'barcode' => '8936012345685',
                'description' => 'Thùng rác nhựa 20L có nắp tiện lợi',
                'category_id' => 21,
                'unit_id' => 1, // Cái
                'selling_price' => 120000,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],

            // Danh mục 22: Đồ chơi & Sản phẩm cho bé
            [
                'name' => 'Bộ đồ chơi xếp hình Lego 100 chi tiết',
                'sku' => 'LGO-001',
                'barcode' => '8936013456789',
                'description' => 'Bộ xếp hình Lego phát triển trí tuệ cho bé',
                'category_id' => 22,
                'unit_id' => 3, // Hộp
                'selling_price' => 250000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'is_active' => true,
                'image_url' => '/storage/piclumen-1747750187180.png'
            ],
            [
                'name' => 'Sữa tắm Johnson’s Baby 500ml',
                'sku' => 'JSB-001',
                'barcode' => '8936013456796',
                'description' => 'Sữa tắm Johnson’s Baby dịu nhẹ cho bé',
                'category_id' => 22,
                'unit_id' => 1, // Chai
                'selling_price' => 85000,
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
