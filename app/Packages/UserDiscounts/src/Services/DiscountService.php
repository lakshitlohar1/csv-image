<?php

namespace CsvImage\UserDiscounts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use CsvImage\UserDiscounts\Models\Discount;
use CsvImage\UserDiscounts\Models\UserDiscount;
use CsvImage\UserDiscounts\Models\DiscountAudit;
use CsvImage\UserDiscounts\Events\DiscountAssigned;
use CsvImage\UserDiscounts\Events\DiscountRevoked;
use CsvImage\UserDiscounts\Events\DiscountApplied;

class DiscountService
{
    protected DiscountCalculationService $calculationService;

    public function __construct(DiscountCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Assign a discount to a user
     */
    public function assign(int $userId, int $discountId, ?int $maxUsage = null, ?\DateTime $expiresAt = null): UserDiscount
    {
        try {
            $discount = Discount::findOrFail($discountId);
            
            if (!$discount->isActive()) {
                throw new \Exception('Discount is not active or has expired');
            }

            if ($discount->isUsageLimitReached()) {
                throw new \Exception('Discount usage limit has been reached');
            }

            return DB::transaction(function () use ($userId, $discount, $maxUsage, $expiresAt) {
                // Check if user already has this discount
                $existingUserDiscount = UserDiscount::where('user_id', $userId)
                    ->where('discount_id', $discount->id)
                    ->first();

                if ($existingUserDiscount && $existingUserDiscount->isActive()) {
                    throw new \Exception('User already has this active discount');
                }

                // Create or update user discount
                $userDiscount = UserDiscount::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'discount_id' => $discount->id,
                    ],
                    [
                        'max_usage' => $maxUsage ?? $discount->per_user_limit,
                        'assigned_at' => now(),
                        'expires_at' => $expiresAt,
                        'is_active' => true,
                    ]
                );

                // Create audit record
                $this->createAuditRecord($userId, $discount->id, $userDiscount->id, 'assigned');

                // Fire event
                event(new DiscountAssigned($discount, $userDiscount, $userId));

                Log::info('Discount assigned successfully', [
                    'user_id' => $userId,
                    'discount_id' => $discount->id,
                    'discount_code' => $discount->code,
                ]);

                return $userDiscount;
            });
        } catch (\Exception $e) {
            Log::error('Failed to assign discount', [
                'user_id' => $userId,
                'discount_id' => $discountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Revoke a discount from a user
     */
    public function revoke(int $userId, int $discountId): bool
    {
        try {
            return DB::transaction(function () use ($userId, $discountId) {
                $userDiscount = UserDiscount::where('user_id', $userId)
                    ->where('discount_id', $discountId)
                    ->where('is_active', true)
                    ->first();

                if (!$userDiscount) {
                    throw new \Exception('Active user discount not found');
                }

                $discount = $userDiscount->discount;

                // Deactivate user discount
                $userDiscount->update(['is_active' => false]);

                // Create audit record
                $this->createAuditRecord($userId, $discountId, $userDiscount->id, 'revoked');

                // Fire event
                event(new DiscountRevoked($discount, $userDiscount, $userId));

                Log::info('Discount revoked successfully', [
                    'user_id' => $userId,
                    'discount_id' => $discountId,
                    'discount_code' => $discount->code,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to revoke discount', [
                'user_id' => $userId,
                'discount_id' => $discountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if user is eligible for a discount
     */
    public function eligibleFor(int $userId, int $discountId): bool
    {
        try {
            $userDiscount = UserDiscount::where('user_id', $userId)
                ->where('discount_id', $discountId)
                ->where('is_active', true)
                ->first();

            if (!$userDiscount) {
                return false;
            }

            return $userDiscount->canUse();
        } catch (\Exception $e) {
            Log::error('Failed to check discount eligibility', [
                'user_id' => $userId,
                'discount_id' => $discountId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Apply discount to an amount
     */
    public function apply(int $userId, int $discountId, float $originalAmount, ?string $orderReference = null): array
    {
        try {
            $userDiscount = UserDiscount::where('user_id', $userId)
                ->where('discount_id', $discountId)
                ->where('is_active', true)
                ->first();

            if (!$userDiscount || !$userDiscount->canUse()) {
                throw new \Exception('User is not eligible for this discount');
            }

            return DB::transaction(function () use ($userDiscount, $originalAmount, $orderReference) {
                // Use lock to prevent concurrent usage
                $lockedUserDiscount = UserDiscount::where('id', $userDiscount->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedUserDiscount->canUse()) {
                    throw new \Exception('Discount is no longer available');
                }

                // Calculate discount amount
                $discountAmount = $this->calculationService->calculate(
                    $lockedUserDiscount->discount,
                    $originalAmount
                );

                $finalAmount = $originalAmount - $discountAmount;

                // Update usage counts
                $lockedUserDiscount->incrementUsage();
                $lockedUserDiscount->discount->incrementUsage();

                // Create audit record
                $this->createAuditRecord(
                    $lockedUserDiscount->user_id,
                    $lockedUserDiscount->discount_id,
                    $lockedUserDiscount->id,
                    'applied',
                    $originalAmount,
                    $discountAmount,
                    $finalAmount,
                    $orderReference
                );

                // Fire event
                event(new DiscountApplied(
                    $lockedUserDiscount->discount,
                    $lockedUserDiscount,
                    $lockedUserDiscount->user_id,
                    $originalAmount,
                    $discountAmount,
                    $finalAmount,
                    $orderReference
                ));

                Log::info('Discount applied successfully', [
                    'user_id' => $lockedUserDiscount->user_id,
                    'discount_id' => $lockedUserDiscount->discount_id,
                    'original_amount' => $originalAmount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'order_reference' => $orderReference,
                ]);

                return [
                    'original_amount' => $originalAmount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'discount_code' => $lockedUserDiscount->discount->code,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to apply discount', [
                'user_id' => $userId,
                'discount_id' => $discountId,
                'original_amount' => $originalAmount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get user's available discounts
     */
    public function getUserDiscounts(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return UserDiscount::where('user_id', $userId)
            ->where('is_active', true)
            ->with('discount')
            ->get()
            ->filter(function ($userDiscount) {
                return $userDiscount->canUse();
            });
    }

    /**
     * Create audit record
     */
    protected function createAuditRecord(
        int $userId,
        int $discountId,
        int $userDiscountId,
        string $action,
        ?float $originalAmount = null,
        ?float $discountAmount = null,
        ?float $finalAmount = null,
        ?string $orderReference = null
    ): DiscountAudit {
        return DiscountAudit::create([
            'user_id' => $userId,
            'discount_id' => $discountId,
            'user_discount_id' => $userDiscountId,
            'action' => $action,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'order_reference' => $orderReference,
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
