<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $suppliersData = [
            // 1. Nhà sản xuất/Nhà phân phối chính thức (FMCG, Điện tử,...)
            [
                'name'           => 'Công ty TNHH Unilever Việt Nam',
                'contact_person' => 'Nguyễn Thị Hoa',
                'email'          => 'info.unilever@unilever.com.vn',
                'phone'          => '+842838290000',
                'address'        => 'Lô A2-A3, KCN Hiệp Phước, Xã Hiệp Phước, Huyện Nhà Bè, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Công ty Cổ phần Sữa Việt Nam (Vinamilk)',
                'contact_person' => 'Lê Văn Hùng',
                'email'          => 'contact@vinamilk.com.vn',
                'phone'          => '+842854155555',
                'address'        => 'Số 10, Đường Thống Nhất, P.11, Q.Gò Vấp, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Tập đoàn Hòa Phát (Gia dụng, Nội thất)',
                'contact_person' => 'Trần Thị Thủy',
                'email'          => 'info@hoaphat.com.vn',
                'phone'          => '+842462810000',
                'address'        => 'KCN Phố Nối A, Xã Giai Phạm, Huyện Yên Mỹ, Hưng Yên',
            ],
            [
                'name'           => 'Công ty TNHH Samsung Vina Electronics',
                'contact_person' => 'Phạm Quang Minh',
                'email'          => 'contact.vn@samsung.com',
                'phone'          => '+842838211211',
                'address'        => 'Tòa nhà Samsung, 2 Nguyễn Cơ Thạch, KĐT Sala, P.An Lợi Đông, Q.2, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Công ty Cổ phần Diana Unicharm',
                'contact_person' => 'Đỗ Thị Lan',
                'email'          => 'cskh@unicharm.com.vn',
                'phone'          => '+842438686666',
                'address'        => 'KCN Phố Nối A, Xã Giai Phạm, Huyện Yên Mỹ, Hưng Yên',
            ],

            // 2. Tổng đại lý/Nhà phân phối lớn (Đa dạng ngành hàng)
            [
                'name'           => 'Tổng Công ty Thương mại Sài Gòn (SATRA)',
                'contact_person' => 'Nguyễn Văn Nam',
                'email'          => 'satra@satra.com.vn',
                'phone'          => '+842838213333',
                'address'        => '275B Phạm Ngũ Lão, P.Phạm Ngũ Lão, Q.1, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Công ty Cổ phần Thế Giới Di Động (Phân phối phụ kiện, đồ gia dụng)',
                'contact_person' => 'Trần Hải Đăng',
                'email'          => 'info@thegioididong.com',
                'phone'          => '+842838125960',
                'address'        => '128 Trần Quang Khải, P.Tân Định, Q.1, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Công ty Cổ phần Dịch vụ Phân phối Tổng hợp Miền Nam (FPT Synnex)',
                'contact_person' => 'Phan Thị Mai',
                'email'          => 'contact@synnexfpt.com.vn',
                'phone'          => '+842873007300',
                'address'        => 'Lô 37-39-41, Đường số 2A, KCN Tân Tạo, Q.Bình Tân, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Công ty Cổ phần Sài Gòn Co.op (Phân phối hàng nhãn riêng, thu mua)',
                'contact_person' => 'Lý Quốc An',
                'email'          => 'info@saigonco-op.vn',
                'phone'          => '+842838360146',
                'address'        => '199-205 Nguyễn Thái Học, P.Phạm Ngũ Lão, Q.1, TP. Hồ Chí Minh',
            ],

            // 3. Nhà cung cấp sỉ (Wholesalers - ví dụ chuyên biệt)
            [
                'name'           => 'Kho Sỉ Đồ Gia Dụng Giá Rẻ Thành Đạt',
                'contact_person' => 'Phạm Văn Hùng',
                'email'          => 'giadungthanhdat@example.com',
                'phone'          => '+84901234567',
                'address'        => 'Cụm công nghiệp Vĩnh Lộc, Huyện Bình Chánh, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Công ty TNHH Thực Phẩm Nhất Hương (nguyên liệu làm bánh)',
                'contact_person' => 'Lê Thị Mai',
                'email'          => 'info@nhatuong.vn',
                'phone'          => '+842862903711',
                'address'        => '147 Công Chúa Ngọc Hân, P.12, Q.11, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Nhà Cung Cấp Văn Phòng Phẩm Minh Khai',
                'contact_person' => 'Đinh Công Tráng',
                'email'          => 'vppminhkhais@example.com',
                'phone'          => '+84912345678',
                'address'        => 'Số 216 Trần Duy Hưng, P.Trung Hòa, Q.Cầu Giấy, Hà Nội',
            ],
            [
                'name'           => 'Sỉ Nông Sản Sạch Bốn Mùa (từ các trang trại liên kết)',
                'contact_person' => 'Nguyễn Thị Dung',
                'email'          => 'nongsansach@example.com',
                'phone'          => '+84977889900',
                'address'        => 'Chợ Đầu Mối Nông Sản Thủ Đức, Q.Thủ Đức, TP. Hồ Chí Minh',
            ],
            [
                'name'           => 'Cửa hàng Đồ Chơi Sỉ Giá Kho',
                'contact_person' => 'Trần Văn Tùng',
                'email'          => 'dochoisigialo@example.com',
                'phone'          => '+84987654321',
                'address'        => 'Chợ Lớn, Q.5, TP. Hồ Chí Minh',
            ],
        ];

        foreach ($suppliersData as $data) {
            Supplier::create($data);
        }
    }
}
