<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique()->comment('Số phiếu nhập kho');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->unsignedInteger('total_items_received')->default(0);
            $table->unsignedInteger('total_value_received')->default(0);
            $table->enum('is_partial', ['true', 'false'])->comment('Đánh dấu nhập một phần hay toàn bộ');
            $table->date('receipt_date');
            $table->foreignId('received_by')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('receipt_number');
            $table->index('purchase_order_id');
            $table->index('receipt_date');
            $table->index('received_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipts');
    }
};