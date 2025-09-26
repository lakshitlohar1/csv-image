<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'file_path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'checksum',
        'upload_id',
        'user_id',
    ];

    /**
     * Get the upload this image belongs to
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * Get the user who uploaded this image
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all variants of this image
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ImageVariant::class);
    }

    /**
     * Get products that use this image as primary
     */
    public function productsAsPrimary(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_image_id');
    }

    /**
     * Get aspect ratio
     */
    public function getAspectRatioAttribute(): float
    {
        if ($this->height === 0) {
            return 0;
        }
        
        return $this->width / $this->height;
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
     * Get dimensions string
     */
    public function getDimensionsAttribute(): string
    {
        return $this->width . 'x' . $this->height;
    }

    /**
     * Check if image is landscape
     */
    public function isLandscape(): bool
    {
        return $this->width > $this->height;
    }

    /**
     * Check if image is portrait
     */
    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    /**
     * Check if image is square
     */
    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    /**
     * Get a specific variant
     */
    public function getVariant(string $variantName): ?ImageVariant
    {
        return $this->variants()->where('variant_name', $variantName)->first();
    }

    /**
     * Get thumbnail variant
     */
    public function thumbnail(): ?ImageVariant
    {
        return $this->getVariant('thumbnail');
    }

    /**
     * Get medium variant
     */
    public function medium(): ?ImageVariant
    {
        return $this->getVariant('medium');
    }

    /**
     * Get large variant
     */
    public function large(): ?ImageVariant
    {
        return $this->getVariant('large');
    }

    /**
     * Scope for images by upload
     */
    public function scopeByUpload($query, $uploadId)
    {
        return $query->where('upload_id', $uploadId);
    }

    /**
     * Scope for images by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}