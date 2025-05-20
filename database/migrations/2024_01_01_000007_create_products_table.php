<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->unique()->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->onDelete('set null');
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedInteger('selling_price');
            $table->string('image_url', 512)->nullable();
            $table->unsignedInteger('min_stock_level')->default(0);
            $table->unsignedInteger('max_stock_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('name', 'idx_prod_name');
            $table->index('barcode', 'idx_prod_barcode');
            $table->index('category_id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};