<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedInteger('sub_total')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('received_money')->default(0)->nullable();
            $table->unsignedInteger('change_money')->default(0)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->foreignId('payment_status_id')->constrained('order_payment_statuses');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('bill_number');
            $table->index('payment_status_id', 'idx_bill_status');
            $table->index('customer_id', 'idx_bill_cust');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bills');
    }
};