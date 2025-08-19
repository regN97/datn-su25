<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('batch_number', 100)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('product_name', 100);
            $table->string('product_sku', 100);
            $table->unsignedInteger('unit_cost')->comment('Đơn giá khi mua');
            $table->unsignedInteger('subtotal')->comment('Tổng giá trị trả lại');
            $table->integer('quantity_returned');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_return_id');
            $table->index('purchase_order_item_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
