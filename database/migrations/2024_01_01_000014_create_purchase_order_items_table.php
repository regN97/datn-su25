<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('product_name', 100);
            $table->string('product_sku', 100);
            $table->unsignedInteger('ordered_quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('quantity_returned')->default(0); // Thêm số lượng trả lại
            $table->unsignedInteger('unit_cost'); // Đổi thành unsignedInteger để thống nhất với bảng purchases
            $table->unsignedInteger('subtotal');  // Đổi thành unsignedInteger để thống nhất với bảng purchases
            $table->unsignedInteger('discount_amount')->default(0)->nullable();
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->text('notes')->nullable(); // Thêm ghi chú cho từng mục
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_order_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
};