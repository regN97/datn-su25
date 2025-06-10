<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 30);
            $table->string('email', 255)->unique()->nullable();
            $table->string('phone', 20)->unique();
            $table->text('address')->nullable();
            $table->unsignedInteger('wallet')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone', 'idx_cust_phone');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};