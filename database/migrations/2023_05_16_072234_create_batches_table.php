<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 100)->unique();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['active', 'low_stock', 'out_of_stock', 'expired', 'damaged'])->default('active');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->date('received_date')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expiry_date', 'idx_batch_expiry');
            $table->index('status', 'idx_batch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
