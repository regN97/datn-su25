<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bill_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('batch_id')->constrained('product_batches')->onDelete('restrict');
            $table->string('p_name', 100);
            $table->string('p_sku', 100);
            $table->string('p_barcode', 100);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_cost');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('discount_per_item')->nullable();
            $table->unsignedInteger('subtotal');
            $table->timestamps();

            $table->index('bill_id');
            $table->index('product_id');
            $table->index('batch_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bill_details');
    }
};