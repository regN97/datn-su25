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

            $table->unsignedInteger('discount_amount')->default(0)->nullable();
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->unsignedInteger('total_amount')->default(0)->comment('Final amount after tax/discount');

            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('po_number');
            $table->index('status_id', 'idx_po_status');
            $table->index('order_date');
            $table->index('created_by');
            $table->index('approved_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};
