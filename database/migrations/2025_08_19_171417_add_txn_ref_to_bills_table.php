<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('bills', function (Blueprint $table) {
        $table->string('txn_ref', 50)->unique()->nullable()->after('bill_number');
    });
}

public function down()
{
    Schema::table('bills', function (Blueprint $table) {
        $table->dropColumn('txn_ref');
    });
}

};
