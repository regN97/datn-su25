<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('batch_id')->constrained('batches')->onDelete('cascade');
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedInteger('initial_quantity')->default(0);
            $table->unsignedInteger('current_quantity')->default(0);
            $table->timestamps();

            $table->unique(['batch_id', 'product_id'], 'unique_batch_product');
            $table->index(['batch_id', 'product_id'], 'idx_batch_product');
            $table->index('product_id', 'idx_bp_product');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_batches');
    }
};
