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
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('batch_number', 100);
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('purchase_price')->default(0);
            $table->integer('initial_quantity')->default(0);
            $table->integer('current_quantity')->default(0);
            $table->enum('status', ['active', 'low_stock', 'out_of_stock', 'expired', 'damaged'])->default('active');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->date('received_date')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'batch_number'], 'unique_product_batch');
            $table->index('expiry_date', 'idx_batch_expiry');
            $table->index('status', 'idx_batch_status');
            $table->index(['product_id', 'status'], 'idx_prod_batch_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_batches');
    }
};