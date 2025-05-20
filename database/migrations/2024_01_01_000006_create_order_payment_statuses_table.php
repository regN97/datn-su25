<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
        });

        // Thêm các trạng thái thanh toán mặc định
        DB::table('order_payment_statuses')->insert([
            ['name' => 'Chưa thanh toán', 'code' => 'UNPAID'],
            ['name' => 'Đã thanh toán', 'code' => 'PAID'],
            ['name' => 'Hoàn tiền', 'code' => 'REFUNDED'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('order_payment_statuses');
    }
};