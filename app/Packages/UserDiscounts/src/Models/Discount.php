<?php

namespace CsvImage\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Discount extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    public function userDiscounts(): HasMany
    {
        return $this->hasMany(UserDiscount::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', \App\Models\User::class),
            'user_discounts'
        )->withPivot(['usage_count', 'max_usage', 'assigned_at', 'expires_at', 'is_active'])
         ->withTimestamps();
    }

    public function audits(): HasMany
    {
        return $this->hasMany(DiscountAudit::class);
    }

    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }

    public function hasUsageLimit(): bool
    {
        return $this->usage_limit !== null;
    }

    public function isUsageLimitReached(): bool
    {
        return $this->hasUsageLimit() && $this->usage_count >= $this->usage_limit;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
