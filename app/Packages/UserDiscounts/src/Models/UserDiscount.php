<?php

namespace CsvImage\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserDiscount extends Model
{
    protected $fillable = [
        'user_id',
        'discount_id',
        'usage_count',
        'max_usage',
        'assigned_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
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
        return $this->max_usage !== null;
    }

    public function isUsageLimitReached(): bool
    {
        return $this->hasUsageLimit() && $this->usage_count >= $this->max_usage;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function canUse(): bool
    {
        return $this->isActive() && 
               !$this->isExpired() && 
               !$this->isUsageLimitReached() &&
               $this->discount->isActive() &&
               !$this->discount->isExpired() &&
               !$this->discount->isUsageLimitReached();
    }
}
