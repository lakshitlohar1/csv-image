<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'category',
        'brand',
        'attributes',
        'is_active',
        'primary_image_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the primary image for this product
     */
    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'primary_image_id');
    }

    /**
     * Get all images associated with this product
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for products by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Check if product has stock
     */
    public function hasStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity > 10) {
            return 'In Stock';
        } elseif ($this->stock_quantity > 0) {
            return 'Low Stock';
        }
        return 'Out of Stock';
    }
}