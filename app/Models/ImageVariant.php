<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_id',
        'variant_name',
        'width',
        'height',
        'file_path',
        'filename',
        'file_size',
        'checksum',
    ];

    /**
     * Get the original image this variant belongs to
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
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
     * Check if this is a thumbnail variant
     */
    public function isThumbnail(): bool
    {
        return $this->variant_name === 'thumbnail';
    }

    /**
     * Check if this is a medium variant
     */
    public function isMedium(): bool
    {
        return $this->variant_name === 'medium';
    }

    /**
     * Check if this is a large variant
     */
    public function isLarge(): bool
    {
        return $this->variant_name === 'large';
    }

    /**
     * Get the URL for this variant
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope for specific variant type
     */
    public function scopeVariant($query, string $variantName)
    {
        return $query->where('variant_name', $variantName);
    }

    /**
     * Scope for thumbnails
     */
    public function scopeThumbnails($query)
    {
        return $query->where('variant_name', 'thumbnail');
    }

    /**
     * Scope for medium variants
     */
    public function scopeMedium($query)
    {
        return $query->where('variant_name', 'medium');
    }

    /**
     * Scope for large variants
     */
    public function scopeLarge($query)
    {
        return $query->where('variant_name', 'large');
    }
}