<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
        });

        // Thêm các vai trò mặc định
        DB::table('user_roles')->insert([
            ['name' => 'Quản trị viên', 'code' => 'ADMIN'],
            ['name' => 'Quản lý', 'code' => 'MANAGER'],
            ['name' => 'Nhân viên bán hàng', 'code' => 'CASHIER'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('user_roles');
    }
};