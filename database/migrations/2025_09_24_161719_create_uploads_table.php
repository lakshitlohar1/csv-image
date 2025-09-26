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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('upload_id')->unique(); // Unique identifier for the upload session
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('checksum'); // MD5 or SHA256 checksum
            $table->integer('total_chunks');
            $table->integer('uploaded_chunks')->default(0);
            $table->json('chunk_checksums')->nullable(); // Store checksums for each chunk
            $table->enum('status', ['pending', 'uploading', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['upload_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
