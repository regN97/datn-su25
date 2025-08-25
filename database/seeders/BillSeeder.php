<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bill;

class BillSeeder extends Seeder
{
    public function run(): void
    {
        $bills = [
            [
                'bill_number'      => 'BILL-0001',
                'customer_id'      => 1,
                'sub_total'        => 150000,
                'discount_amount'  => 10000,
                'total_amount'     => 140000,
                'received_money'   => 150000,
                'change_money'     => 10000,
                'payment_method'   => 'cash',
                'payment_status_id'=> 2,
                'notes'            => 'Khách thanh toán tiền mặt',
                'cashier_id'       => 1,
            ],
            [
                'bill_number'      => 'BILL-0002',
                'customer_id'      => 2,
                'sub_total'        => 250000,
                'discount_amount'  => 0,
                'total_amount'     => 250000,
                'received_money'   => 250000,
                'change_money'     => 0,
                'payment_method'   => 'card',
                'payment_status_id'=> 2,
                'notes'            => 'Thanh toán qua thẻ',
                'cashier_id'       => 1,
            ],
        ];

        foreach ($bills as $bill) {
            Bill::create($bill);
        }
    }
}
