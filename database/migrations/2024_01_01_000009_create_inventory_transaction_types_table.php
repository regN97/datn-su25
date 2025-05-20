<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->string('code')->unique();
        });

        // Thêm các loại giao dịch mặc định
        DB::table('inventory_transaction_types')->insert([
            ['name' => 'Nhập kho', 'code' => 'IMPORT'],
            ['name' => 'Xuất kho', 'code' => 'EXPORT'],
            ['name' => 'Điều chỉnh', 'code' => 'ADJUST'],
            ['name' => 'Trả hàng', 'code' => 'RETURN'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transaction_types');
    }
};