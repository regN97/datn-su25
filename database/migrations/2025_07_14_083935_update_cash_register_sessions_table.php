<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            // Xóa cột shifts
            $table->dropColumn('shifts');

            // Thêm cột user_shift_id làm khóa ngoại
            $table->unsignedBigInteger('user_shift_id')->nullable()->after('user_id');

            // Thay đổi các cột số tiền thành decimal
            $table->decimal('opening_amount', 10, 2)->unsigned()->change();
            $table->decimal('closing_amount', 10, 2)->unsigned()->nullable()->change();
            $table->decimal('actual_amount', 10, 2)->unsigned()->nullable()->change();
            $table->decimal('difference', 10, 2)->nullable()->change();

            // Thêm khóa ngoại
            $table->foreign('user_shift_id')->references('id')->on('user_shifts')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            // Khôi phục cột shifts
            $table->string('shifts', 100)->nullable()->after('user_id');

            // Xóa cột user_shift_id và khóa ngoại
            $table->dropForeign(['user_shift_id']);
            $table->dropColumn('user_shift_id');

            // Khôi phục các cột số tiền về int
            $table->unsignedInteger('opening_amount')->change();
            $table->unsignedInteger('closing_amount')->nullable()->change();
            $table->unsignedInteger('actual_amount')->nullable()->change();
            $table->integer('difference')->nullable()->change();
        });
    }
};