<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Danh mục chính
            ['id' => 1, 'name' => 'Thực phẩm đóng gói', 'parent_id' => null, 'description' => 'Thực phẩm chế biến sẵn, đóng gói như mì, gạo, đồ hộp'],
            ['id' => 2, 'name' => 'Đồ uống', 'parent_id' => null, 'description' => 'Nước giải khát, cà phê, trà, nước tăng lực'],
            ['id' => 3, 'name' => 'Thiết bị gia dụng', 'parent_id' => null, 'description' => 'Thiết bị nhà bếp, đồ điện gia dụng'],
            ['id' => 4, 'name' => 'Chăm sóc cá nhân', 'parent_id' => null, 'description' => 'Sản phẩm vệ sinh, chăm sóc cá nhân'],
            ['id' => 5, 'name' => 'Vệ sinh nhà cửa', 'parent_id' => null, 'description' => 'Sản phẩm tẩy rửa, vệ sinh nhà cửa'],
            ['id' => 6, 'name' => 'Đồ ăn vặt & Bánh kẹo', 'parent_id' => null, 'description' => 'Snack, bánh kẹo, đồ ăn vặt'],
            ['id' => 7, 'name' => 'Gia vị & Phụ liệu', 'parent_id' => null, 'description' => 'Gia vị nấu ăn, phụ liệu chế biến'],
            ['id' => 8, 'name' => 'Sức khỏe & Dinh dưỡng', 'parent_id' => null, 'description' => 'Thực phẩm chức năng, vitamin, dinh dưỡng'],
            ['id' => 9, 'name' => 'Mẹ & Bé', 'parent_id' => null, 'description' => 'Sản phẩm dành cho mẹ và bé'],

            // Danh mục con của "Đồ uống"
            ['id' => 10, 'name' => 'Nước giải khát', 'parent_id' => 2, 'description' => 'Nước ngọt có ga, nước tăng lực'],
            ['id' => 11, 'name' => 'Nước suối & Nước khoáng', 'parent_id' => 2, 'description' => 'Nước tinh khiết, nước khoáng thiên nhiên'],
            ['id' => 12, 'name' => 'Cà phê & Trà', 'parent_id' => 2, 'description' => 'Cà phê đóng gói, trà túi lọc'],

            // Danh mục con của "Thực phẩm đóng gói"
            ['id' => 13, 'name' => 'Mì & Phở ăn liền', 'parent_id' => 1, 'description' => 'Các loại mì gói, phở ăn liền'],
            ['id' => 14, 'name' => 'Đồ hộp & Chế biến', 'parent_id' => 1, 'description' => 'Thực phẩm đóng hộp, chế biến sẵn'],
            ['id' => 15, 'name' => 'Gạo & Ngũ cốc', 'parent_id' => 1, 'description' => 'Gạo các loại, ngũ cốc dinh dưỡng'],
            ['id' => 16, 'name' => 'Đồ ăn vặt', 'parent_id' => 1, 'description' => 'Bim bim, bánh kẹo'],

            // Danh mục con của "Mẹ & Bé"
            ['id' => 17, 'name' => 'Sữa bột & Dinh dưỡng', 'parent_id' => 9, 'description' => 'Sữa bột, bột ăn dặm cho bé'],
            ['id' => 18, 'name' => 'Tã & Khăn ướt', 'parent_id' => 9, 'description' => 'Tã giấy, khăn ướt cho bé'],
            ['id' => 19, 'name' => 'Đồ dùng cho bé', 'parent_id' => 9, 'description' => 'Bình sữa, núm ti, đồ dùng cho bé'],

            // Danh mục con của "Sức khỏe & Dinh dưỡng"
            ['id' => 20, 'name' => 'Vitamin & TPCN', 'parent_id' => 8, 'description' => 'Vitamin tổng hợp, thực phẩm chức năng'],
            ['id' => 21, 'name' => 'Protein & Thể thao', 'parent_id' => 8, 'description' => 'Sữa protein, thực phẩm thể thao'],
            ['id' => 22, 'name' => 'Thảo dược & DMP', 'parent_id' => 8, 'description' => 'Thảo dược, dược mỹ phẩm']
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
