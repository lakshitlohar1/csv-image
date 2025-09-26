<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('discount_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('discount_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_discount_id')->constrained()->onDelete('cascade');
            $table->string('action'); // assigned, revoked, applied, expired
            $table->decimal('original_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);
            $table->json('metadata')->nullable();
            $table->string('order_reference')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index(['discount_id', 'action']);
            $table->index('order_reference');
        });
    }

    public function down()
    {
        Schema::dropIfExists('discount_audits');
    }
};
