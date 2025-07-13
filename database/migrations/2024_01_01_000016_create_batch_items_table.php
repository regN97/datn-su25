<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('purchase_order_item_id')
                ->nullable()
                ->constrained('purchase_order_items')
                ->onDelete('restrict');
            // Thông tin số lượng
            $table->unsignedInteger('ordered_quantity');   // Số lượng đặt trong PO
            $table->unsignedInteger('received_quantity');  // Số lượng đã nhận trong đợt này
            $table->unsignedInteger('rejected_quantity')->default(0); // Số lượng từ chối nhận
            $table->unsignedInteger('remaining_quantity'); // Số lượng còn lại cần nhận
            $table->unsignedInteger('current_quantity');   // Số lượng hiện tại còn trong kho

            // Thông tin giá
            $table->unsignedInteger('purchase_price');
            $table->unsignedInteger('total_amount');

            // Thông tin sản phẩm
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Trạng thái tồn kho
            $table->enum('inventory_status', [
                'active',
                'low_stock',
                'out_of_stock',
                'expired',
                'damaged'
            ])->default('active');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['batch_id', 'product_id']);
            $table->index(['product_id', 'expiry_date']);
            $table->index('inventory_status');
        });

    }

    public function down()
    {
        Schema::dropIfExists('batch_items');
    }
};
