<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('discount_id')->constrained()->onDelete('cascade');
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage')->nullable();
            $table->datetime('assigned_at');
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'discount_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['discount_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_discounts');
    }
};
