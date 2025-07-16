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
            $table->string('batch_number', 100);
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');  // Thêm liên kết với PO
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->date('received_date');
            $table->string('invoice_number', 100)->nullable();

            $table->unsignedInteger('discount_amount')->default(0)->nullable();
            $table->enum('discount_type', ['percent', 'amount'])->nullable();

            $table->unsignedInteger('total_amount')->default(0);
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card'])->nullable();
            $table->date('payment_date')->nullable();
            $table->unsignedInteger('paid_amount')->default(0);
            $table->unsignedInteger('remaining_amount')->default(0);

            $table->enum('receipt_status', ['partially_received', 'completed'])->default('completed');

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('batch_number', 'idx_batch_number');
            $table->index('receipt_status', 'idx_receipt_status');
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
