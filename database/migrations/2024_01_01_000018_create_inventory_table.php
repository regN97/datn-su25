<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->constrained('product_batches')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock'])->default('out_of_stock');
            $table->timestamps();

            $table->index('product_id');
            $table->index('batch_id');
            $table->index('stock_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory');
    }
};