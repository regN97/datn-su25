<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->text('description')->nullable();
            $table->softDeletes();
        });

        DB::table('work_shifts')->insert([
            ['name' => 'Ca sáng', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'description' => 'Ca làm việc từ 6h đến 14h'],
            ['name' => 'Ca chiều','start_time' => '14:00:00', 'end_time' => '22:00:00', 'description' => 'Ca làm việc từ 14h đến 22h'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('work_shifts');
    }
};