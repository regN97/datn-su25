<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['id' => 1, 'name' => 'Thực phẩm đóng gói', 'parent_id' => null, 'description' => 'Thực phẩm đã qua chế biến, đóng gói sẵn như mì, bánh kẹo, đồ hộp'],
            ['id' => 2, 'name' => 'Đồ uống', 'parent_id' => null, 'description' => 'Nước ngọt, nước suối, nước ép, cà phê, trà'],
            ['id' => 3, 'name' => 'Đồ gia dụng nhỏ', 'parent_id' => null, 'description' => 'Dụng cụ nhà bếp, thiết bị gia dụng nhỏ như máy xay, bình đun nước'],
            ['id' => 4, 'name' => 'Vệ sinh cá nhân', 'parent_id' => null, 'description' => 'Sữa tắm, dầu gội, kem đánh răng, giấy vệ sinh'],
            ['id' => 5, 'name' => 'Đồ dùng gia đình', 'parent_id' => null, 'description' => 'Chổi, giẻ lau, dụng cụ nhà cửa'],
            ['id' => 6, 'name' => 'Đồ ăn nhẹ và bánh kẹo', 'parent_id' => null, 'description' => 'Snack, bánh quy, kẹo ngọt, socola'],
            ['id' => 7, 'name' => 'Thực phẩm đông lạnh', 'parent_id' => null, 'description' => 'Thịt đông lạnh, hải sản đông lạnh (chưa làm tươi)'],
            ['id' => 8, 'name' => 'Gia vị và nguyên liệu', 'parent_id' => null, 'description' => 'Muối, đường, bột ngọt, dầu ăn, nước mắm'],
            ['id' => 9, 'name' => 'Thực phẩm chay', 'parent_id' => null, 'description' => 'Các loại đậu, tàu hủ, rau củ sấy khô (không tươi)'],
            ['id' => 10, 'name' => 'Đồ dùng cá nhân', 'parent_id' => null, 'description' => 'Khăn giấy, băng vệ sinh, tã giấy'],

            // Danh mục con của "Đồ uống"
            ['id' => 11, 'name' => 'Nước ngọt', 'parent_id' => 2, 'description' => 'Các loại soda, nước ngọt có ga'],
            ['id' => 12, 'name' => 'Nước ép trái cây', 'parent_id' => 2, 'description' => 'Nước ép từ trái cây tự nhiên'],
            ['id' => 13, 'name' => 'Trà - Cà phê', 'parent_id' => 2, 'description' => 'Trà túi lọc, cà phê hòa tan, cà phê rang xay'],

            // Danh mục con của "Thực phẩm đóng gói"
            ['id' => 14, 'name' => 'Mì ăn liền', 'parent_id' => 1, 'description' => 'Các loại mì gói, mì ly ăn liền'],
            ['id' => 15, 'name' => 'Đồ hộp', 'parent_id' => 1, 'description' => 'Các loại thịt hộp, cá hộp, đồ ăn đóng lon'],

            // Danh mục con của "Đồ ăn nhẹ và bánh kẹo"
            ['id' => 16, 'name' => 'Snack', 'parent_id' => 6, 'description' => 'Snack khoai tây, snack ngô, snack đa dạng'],
            ['id' => 17, 'name' => 'Bánh kẹo', 'parent_id' => 6, 'description' => 'Bánh quy, kẹo ngọt, socola'],

            // Danh mục con của "Đồ dùng cá nhân"
            ['id' => 18, 'name' => 'Khăn giấy', 'parent_id' => 10, 'description' => 'Khăn giấy ăn, khăn giấy vệ sinh'],
            ['id' => 19, 'name' => 'Băng vệ sinh', 'parent_id' => 10, 'description' => 'Các loại băng vệ sinh dùng hàng ngày'],
            ['id' => 20, 'name' => 'Tã giấy', 'parent_id' => 10, 'description' => 'Tã giấy cho bé và người lớn'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
