<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'customer_name' => 'Nguyễn Văn Hùng',
                'email' => 'hung.nguyen@g7mart.vn',
                'phone' => '0901234567',
                'address' => '123 Lê Lợi, Quận 1, TP.HCM',
                'wallet' => 300000,
            ],
            [
                'customer_name' => 'Trần Thị Mai',
                'email' => 'mai.tran@g7mart.vn',
                'phone' => '0912345678',
                'address' => '45 Nguyễn Huệ, Huế',
                'wallet' => 500000,
            ],
            [
                'customer_name' => 'Lê Quang Vinh',
                'email' => null,
                'phone' => '0923456789',
                'address' => '78 Trần Phú, Đà Nẵng',
                'wallet' => 150000,
            ],
            [
                'customer_name' => 'Phạm Thị Lan',
                'email' => 'lan.pham@g7mart.vn',
                'phone' => '0934567890',
                'address' => null,
                'wallet' => 400000,
            ],
            [
                'customer_name' => 'Hoàng Văn Nam',
                'email' => 'nam.hoang@g7mart.vn',
                'phone' => '0945678901',
                'address' => '56 Hai Bà Trưng, Hà Nội',
                'wallet' => 600000,
            ],
            [
                'customer_name' => 'Vũ Thị Thảo',
                'email' => 'thao.vu@g7mart.vn',
                'phone' => '0956789012',
                'address' => '89 Nguyễn Trãi, TP.HCM',
                'wallet' => 250000,
            ],
            [
                'customer_name' => 'Đỗ Văn Tùng',
                'email' => null,
                'phone' => '0967890123',
                'address' => '34 Lê Duẩn, Đà Nẵng',
                'wallet' => 700000,
            ],
            [
                'customer_name' => 'Bùi Thị Hồng',
                'email' => 'hong.bui@g7mart.vn',
                'phone' => '0978901234',
                'address' => '67 Phạm Văn Đồng, Hà Nội',
                'wallet' => 350000,
            ],
            [
                'customer_name' => 'Nguyễn Thị Ngọc',
                'email' => 'ngoc.nguyen@g7mart.vn',
                'phone' => '0989012345',
                'address' => null,
                'wallet' => 200000,
            ],
            [
                'customer_name' => 'Trần Văn Khoa',
                'email' => 'khoa.tran@g7mart.vn',
                'phone' => '0990123456',
                'address' => '23 Nguyễn Văn Cừ, Vinh',
                'wallet' => 800000,
            ],
            [
                'customer_name' => 'Lê Thị Hương',
                'email' => 'huong.le@g7mart.vn',
                'phone' => '0902345678',
                'address' => '45 Lý Thường Kiệt, Nha Trang',
                'wallet' => 450000,
            ],
            [
                'customer_name' => 'Phạm Văn Đức',
                'email' => null,
                'phone' => '0913456789',
                'address' => '78 Hùng Vương, Cần Thơ',
                'wallet' => 550000,
            ],
            [
                'customer_name' => 'Hoàng Thị Yến',
                'email' => 'yen.hoang@g7mart.vn',
                'phone' => '0924567890',
                'address' => '12 Trần Hưng Đạo, Hải Phòng',
                'wallet' => 650000,
            ],
            [
                'customer_name' => 'Vũ Văn Bình',
                'email' => 'binh.vu@g7mart.vn',
                'phone' => '0935678901',
                'address' => null,
                'wallet' => 100000,
            ],
            [
                'customer_name' => 'Đỗ Thị Linh',
                'email' => 'linh.do@g7mart.vn',
                'phone' => '0946789012',
                'address' => '56 Nguyễn Thị Minh Khai, TP.HCM',
                'wallet' => 750000,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}