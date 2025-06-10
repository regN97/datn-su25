<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_type_id')->constrained('inventory_transaction_types');
            $table->integer('quantity_change')->comment('Số lượng thay đổi');
            $table->unsignedInteger('unit_price')->nullable()->comment('Giá tiền 1 đơn vị ở thời điểm thực hiện giao dịch');
            $table->unsignedInteger('total_value')->nullable()->comment('quantity_change * unit_price');
            $table->timestamp('transaction_date')->useCurrent();
            $table->foreignId('related_bill_id')->nullable()->constrained('bills')->onDelete('set null');
            $table->foreignId('related_purchase_return_id')->nullable()->constrained('purchase_returns')->onDelete('set null');
            $table->foreignId('related_batch_id')->nullable()->constrained('batches')->onDelete('set null');
            $table->foreignId('user_id')->comment('Người thực hiện')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('transaction_date', 'idx_inv_trans_date');
            $table->index('related_bill_id');
            $table->index('related_purchase_return_id');
            $table->index('related_batch_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
