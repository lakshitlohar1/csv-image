<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('discount_audits', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable()->change();
            $table->decimal('discount_amount', 10, 2)->nullable()->change();
            $table->decimal('final_amount', 10, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('discount_audits', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable(false)->change();
            $table->decimal('discount_amount', 10, 2)->nullable(false)->change();
            $table->decimal('final_amount', 10, 2)->nullable(false)->change();
        });
    }
};