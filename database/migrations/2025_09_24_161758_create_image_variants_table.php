<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('image_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained()->onDelete('cascade');
            $table->string('variant_name'); // 'thumbnail', 'medium', 'large'
            $table->integer('width');
            $table->integer('height');
            $table->string('file_path');
            $table->string('filename');
            $table->bigInteger('file_size');
            $table->string('checksum');
            $table->timestamps();
            
            $table->index(['image_id', 'variant_name']);
            $table->unique(['image_id', 'variant_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_variants');
    }
};
