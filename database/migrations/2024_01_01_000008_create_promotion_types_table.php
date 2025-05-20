<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promotion_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code')->unique();
        });

        // Thêm các loại khuyến mãi mặc định
        DB::table('promotion_types')->insert([
            ['name' => 'Giảm giá trực tiếp', 'code' => 'DIRECT_DISCOUNT'],
            ['name' => 'Giảm giá phần trăm', 'code' => 'PERCENTAGE_DISCOUNT'],
            ['name' => 'Mua X tặng Y', 'code' => 'BUY_X_GET_Y'],
            ['name' => 'Giảm giá đơn hàng', 'code' => 'ORDER_DISCOUNT'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('promotion_types');
    }
};