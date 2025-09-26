<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed', 'buy_x_get_y']);
            $table->decimal('value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('per_user_limit')->nullable();
            $table->datetime('starts_at');
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'expires_at']);
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('discounts');
    }
};
