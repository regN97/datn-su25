<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->foreignId('type_id')->constrained('promotion_types')->onDelete('restrict');
            $table->decimal('discount_value', 10, 2)->nullable()->comment('Sử dụng khi type_id là DIRECT_DISCOUNT hoặc PERCENTAGE_DISCOUNT');
            $table->decimal('min_order_value', 10, 2)->nullable()->comment('Sử dụng khi type_id là ORDER_DISCOUNT');
            $table->integer('buy_quantity')->nullable()->comment('Sử dụng khi type_id là BUY_X_GET_Y');
            $table->integer('get_quantity')->nullable()->comment('Sử dụng khi type_id là BUY_X_GET_Y');
            $table->string('coupon_code', 50)->unique();
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_customer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('coupon_code');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotions');
    }
};