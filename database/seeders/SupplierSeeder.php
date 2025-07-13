<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $suppliersData = [
            // Tập đoàn FMCG lớn
            [
                'name' => 'Công ty TNHH Unilever Việt Nam',
                'contact_person' => 'Nguyễn Thị Hoa',
                'email' => 'business.contact@unilever.com.vn',
                'phone' => '02838290000',
                'address' => 'Lô A2-A3, KCN Hiệp Phước, Xã Hiệp Phước, Huyện Nhà Bè, TP. Hồ Chí Minh',
            ],
            [
                'name' => 'Công ty Cổ phần Sữa Việt Nam (Vinamilk)',
                'contact_person' => 'Lê Văn Hùng',
                'email' => 'wholesale@vinamilk.com.vn',
                'phone' => '02854155555',
                'address' => 'Số 10, Đường Thống Nhất, P.11, Q.Gò Vấp, TP. Hồ Chí Minh',
            ],

            // Nhà phân phối thực phẩm & đồ uống
            [
                'name' => 'Công ty CP Thực phẩm PepsiCo Việt Nam',
                'contact_person' => 'Trần Minh Đức',
                'email' => 'sales.vn@pepsico.com',
                'phone' => '02838242222',
                'address' => 'Tầng 5, Sheraton Plaza, 175 Hai Bà Trưng, P.6, Q.3, TP. Hồ Chí Minh',
            ],
            [
                'name' => 'Công ty TNHH Nestlé Việt Nam',
                'contact_person' => 'Phạm Thu Trang',
                'email' => 'wholesale.vn@nestle.com',
                'phone' => '02837999333',
                'address' => 'Tầng 10, Toà nhà Lazada One, 3/2, P.11, Q.10, TP. Hồ Chí Minh',
            ],

            // Nhà cung cấp đồ gia dụng
            [
                'name' => 'Công ty CP Điện Gia Dụng Sunhouse',
                'contact_person' => 'Lê Hoàng Nam',
                'email' => 'business@sunhouse.com.vn',
                'phone' => '02437921111',
                'address' => '139 Quang Trung, P.Quang Trung, Q.Hà Đông, Hà Nội',
            ],

            // Nhà phân phối sản phẩm chăm sóc cá nhân
            [
                'name' => 'Công ty TNHH P&G Việt Nam',
                'contact_person' => 'Nguyễn Thị Mai Anh',
                'email' => 'wholesale.vn@pg.com',
                'phone' => '02838240500',
                'address' => 'Tầng 12, Tòa nhà Metropolitan, 235 Đồng Khởi, P.Bến Nghé, Q.1, TP. Hồ Chí Minh',
            ],

            // Nhà cung cấp thực phẩm đông lạnh
            [
                'name' => 'Công ty CP Việt Nam Food Industries',
                'contact_person' => 'Trần Văn Khoa',
                'email' => 'sales@vnfood.com.vn',
                'phone' => '02838333666',
                'address' => 'KCN Tân Tạo, P.Tân Tạo A, Q.Bình Tân, TP. Hồ Chí Minh',
            ],

            // Nhà phân phối thực phẩm chức năng
            [
                'name' => 'Công ty TNHH Dược phẩm Mega Lifesciences',
                'contact_person' => 'Đỗ Thị Hương',
                'email' => 'contact.vn@megalife.com',
                'phone' => '02838224555',
                'address' => '6F Mê Linh Point, 2 Ngô Đức Kế, P.Bến Nghé, Q.1, TP. Hồ Chí Minh',
            ],

            // Nhà cung cấp sản phẩm cho mẹ và bé
            [
                'name' => 'Công ty TNHH Mead Johnson Nutrition Việt Nam',
                'contact_person' => 'Nguyễn Thị Ngọc',
                'email' => 'wholesale.vn@meadjohnson.com',
                'phone' => '02838277999',
                'address' => 'Tầng 16, Tòa nhà Sunwah, 115 Nguyễn Huệ, P.Bến Nghé, Q.1, TP. Hồ Chí Minh',
            ],

            // Nhà phân phối gia vị và phụ gia thực phẩm
            [
                'name' => 'Công ty CP Masan Consumer',
                'contact_person' => 'Lê Thị Thu Hà',
                'email' => 'wholesale@masan.com.vn',
                'phone' => '02854163999',
                'address' => 'Tầng 12, Tòa nhà MPlaza Saigon, 39 Lê Duẩn, P.Bến Nghé, Q.1, TP. Hồ Chí Minh',
            ],
        ];

        foreach ($suppliersData as $supplier) {
            Supplier::create($supplier);
        }
    }
}
