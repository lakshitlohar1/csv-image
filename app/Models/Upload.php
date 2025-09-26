<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'checksum',
        'total_chunks',
        'uploaded_chunks',
        'chunk_checksums',
        'status',
        'error_message',
        'user_id',
        'completed_at',
    ];

    protected $casts = [
        'chunk_checksums' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all images associated with this upload
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Check if upload is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if upload is in progress
     */
    public function isUploading(): bool
    {
        return $this->status === 'uploading';
    }

    /**
     * Check if upload has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get upload progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_chunks === 0) {
            return 0;
        }
        
        return (int) round(($this->uploaded_chunks / $this->total_chunks) * 100);
    }

    /**
     * Mark upload as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark upload as failed
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for completed uploads
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed uploads
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending uploads
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}