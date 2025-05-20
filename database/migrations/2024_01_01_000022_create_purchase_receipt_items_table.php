<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained('purchase_receipts')->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('batch_number', 100)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('product_name', 100);
            $table->string('product_sku', 100);
            $table->integer('quantity_received');
            $table->unsignedInteger('unit_cost');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_receipt_id');
            $table->index('purchase_order_item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipt_items');
    }
};