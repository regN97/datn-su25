<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       
        $userIds = [1, 2, 3];
        $shiftIdMorning = 1; 
        $shiftIdAfternoon = 2; 

        // Tạo 10 bản ghi cho mỗi người dùng, tất cả đều hoàn thành.
        foreach ($userIds as $userId) {
            for ($i = 0; $i < 10; $i++) {
                // Chọn ngẫu nhiên Ca Sáng hoặc Ca Chiều
                $shiftId = rand(0, 1) ? $shiftIdMorning : $shiftIdAfternoon;

                // Tạo ngày ngẫu nhiên trong vòng 30 ngày gần đây
                $date = Carbon::now()->subDays(rand(1, 30))->toDateString();

                // Tạo giờ check-in và check-out ngẫu nhiên
                $checkIn = Carbon::parse($date)->setHour(rand(7, 10))->setMinute(rand(0, 59));
                $checkOut = Carbon::parse($date)->setHour(rand(16, 21))->setMinute(rand(0, 59));

                DB::table('user_shifts')->insert([
                    'user_id' => $userId,
                    'shift_id' => $shiftId,
                    'date' => $date,
                    'status' => 'COMPLETED',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
