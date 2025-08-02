<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('po_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('code')->unique();
        });

        // Thêm các trạng thái đơn đặt hàng mặc định
        DB::table('po_statuses')->insert([
            ['name' => 'Nháp', 'code' => 'DRAFT'],
            ['name' => 'Chờ nhập', 'code' => 'PENDING'],
            ['name' => 'Nhập một phần', 'code' => 'PARTIAL RECEIVED'],
            ['name' => 'Nhập toàn bộ', 'code' => 'FULLY RECEIVED'],
            ['name' => 'Đã hủy', 'code' => 'CANCELLED'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('po_statuses');
    }
};
