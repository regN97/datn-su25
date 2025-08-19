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
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('product_units')->onDelete('restrict');
            $table->unsignedInteger('selling_price');
            $table->string('image_url', 512)->nullable();
            $table->unsignedInteger('min_stock_level')->default(0);
            $table->unsignedInteger('max_stock_level')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);     // Tồn kho hiện tại
            $table->date('last_received_at')->nullable();              // Ngày nhập hàng gần nhất
            $table->date('last_sold_at')->nullable();                  // Ngày bán hàng gần nhất
            $table->boolean('is_trackable')->default(true);            // Có quản lý tồn kho không (false cho dịch vụ, sản phẩm ảo)
            $table->unsignedInteger('reorder_point')->default(0);      // Khi stock_quantity <= reorder_point → cần nhập thêm
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
