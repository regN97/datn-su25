<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('work_shifts')->onDelete('restrict');
            $table->date('date');
            $table->enum('status', [
                'SCHEDULED',
                'CONFIRMED',
                'CHECKED_IN',
                'CHECKED_OUT',
                'COMPLETED',
                'ABSENT',
                'LEAVE',
                'SICK_LEAVE',
                'CANCELLED'
            ])->default('SCHEDULED');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('shift_id');
            $table->index('date');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_shifts');
    }
};