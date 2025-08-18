<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Remove foreign key constraint and index from user_shifts
        Schema::table('user_shifts', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['shift_id']); // Drop index before dropping column
            $table->dropColumn('shift_id');
        });

        // Step 2: Drop the work_shifts table
        Schema::dropIfExists('work_shifts');

        // Step 3: Add total_hours column to user_shifts
        Schema::table('user_shifts', function (Blueprint $table) {
            $table->decimal('total_hours', 5, 2)->nullable()->comment('Tổng số giờ làm việc (tính bằng giờ)')->after('check_out');
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop the trigger
        DB::unprepared('DROP TRIGGER IF EXISTS calculate_total_hours');

        // Step 2: Remove total_hours column from user_shifts
        Schema::table('user_shifts', function (Blueprint $table) {
            $table->dropColumn('total_hours');
        });

        // Step 3: Recreate work_shifts table
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->text('description')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        // Step 4: Restore shift_id column, index and foreign key in user_shifts
        Schema::table('user_shifts', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->after('user_id');
            $table->index('shift_id'); // Restore index
            $table->foreign('shift_id')->references('id')->on('work_shifts')->onDelete('cascade');
        });

        
    }
};