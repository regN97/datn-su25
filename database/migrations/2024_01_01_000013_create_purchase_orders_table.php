<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('po_statuses')->onDelete('restrict');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();

            $table->unsignedInteger('subtotal_amount')->default(0)->comment('Sum of all items before tax/discount');
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('total_amount')->default(0)->comment('Final amount after tax/discount');

            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'overdue'])->default('unpaid');
            $table->string('payment_terms')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit', 'check', 'other'])->nullable();
            $table->date('payment_due_date')->nullable();
            $table->unsignedInteger('amount_paid')->default(0)->comment('Số tiền đã trả');
            $table->unsignedInteger('balance_due')->default(0)->comment('Số tiền còn lại');

            // Thông tin nhập kho
            $table->enum('received_status', ['pending', 'partial', 'fully'])->default('pending');

            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('po_number');
            $table->index('supplier_id', 'idx_po_supplier');
            $table->index('status_id', 'idx_po_status');
            $table->index('order_date');
            $table->index('payment_status');
            $table->index('received_status');
            $table->index('created_by');
            $table->index('approved_by');
            $table->index(['status_id', 'payment_status']);
            $table->index(['supplier_id', 'status_id']);
            $table->index(['payment_due_date', 'payment_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};
