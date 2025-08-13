<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique()->comment('Số phiếu trả hàng');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->unsignedInteger('total_items_returned')->default(0);
            $table->unsignedInteger('total_value_returned')->default(0);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');

            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_order_id');
            $table->index('return_number');
            $table->index('return_date');
            $table->index('supplier_id');
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_returns');
    }
};
