<?php

namespace CsvImage\UserDiscounts\Services;

use CsvImage\UserDiscounts\Models\Discount;

class DiscountCalculationService
{
    /**
     * Calculate discount amount based on discount type and rules
     */
    public function calculate(Discount $discount, float $originalAmount): float
    {
        $discountAmount = 0;

        switch ($discount->type) {
            case 'percentage':
                $discountAmount = $this->calculatePercentageDiscount($discount, $originalAmount);
                break;
            case 'fixed':
                $discountAmount = $this->calculateFixedDiscount($discount, $originalAmount);
                break;
            case 'buy_x_get_y':
                $discountAmount = $this->calculateBuyXGetYDiscount($discount, $originalAmount);
                break;
        }

        // Apply minimum order amount check
        if ($discount->min_order_amount && $originalAmount < $discount->min_order_amount) {
            return 0;
        }

        // Apply maximum discount amount cap
        if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
            $discountAmount = $discount->max_discount_amount;
        }

        // Apply global caps from config
        $maxPercentage = config('user-discounts.caps.max_percentage', 100);
        $maxFixedAmount = config('user-discounts.caps.max_fixed_amount', 1000);

        if ($discount->type === 'percentage' && $discountAmount > ($originalAmount * $maxPercentage / 100)) {
            $discountAmount = $originalAmount * $maxPercentage / 100;
        }

        if ($discount->type === 'fixed' && $discountAmount > $maxFixedAmount) {
            $discountAmount = $maxFixedAmount;
        }

        // Apply rounding
        return $this->applyRounding($discountAmount);
    }

    /**
     * Calculate percentage discount
     */
    protected function calculatePercentageDiscount(Discount $discount, float $originalAmount): float
    {
        return ($originalAmount * $discount->value) / 100;
    }

    /**
     * Calculate fixed amount discount
     */
    protected function calculateFixedDiscount(Discount $discount, float $originalAmount): float
    {
        return min($discount->value, $originalAmount);
    }

    /**
     * Calculate buy X get Y discount
     */
    protected function calculateBuyXGetYDiscount(Discount $discount, float $originalAmount): float
    {
        $conditions = $discount->conditions ?? [];
        $buyX = $conditions['buy_x'] ?? 1;
        $getY = $conditions['get_y'] ?? 1;
        $itemPrice = $conditions['item_price'] ?? 1;

        $eligibleItems = floor($originalAmount / $itemPrice);
        $discountItems = floor($eligibleItems / $buyX) * $getY;

        return $discountItems * $itemPrice;
    }

    /**
     * Apply rounding based on configuration
     */
    protected function applyRounding(float $amount): float
    {
        $method = config('user-discounts.rounding.method', 'round');
        $precision = config('user-discounts.rounding.precision', 2);

        switch ($method) {
            case 'floor':
                return floor($amount * pow(10, $precision)) / pow(10, $precision);
            case 'ceil':
                return ceil($amount * pow(10, $precision)) / pow(10, $precision);
            case 'round':
            default:
                return round($amount, $precision);
        }
    }

    /**
     * Calculate stacked discounts
     */
    public function calculateStackedDiscounts(array $userDiscounts, float $originalAmount): array
    {
        $stackingOrder = config('user-discounts.stacking.order', []);
        $totalDiscount = 0;
        $appliedDiscounts = [];

        // Sort discounts by stacking order
        $sortedDiscounts = $this->sortDiscountsByStackingOrder($userDiscounts, $stackingOrder);

        foreach ($sortedDiscounts as $userDiscount) {
            if (!$userDiscount->canUse()) {
                continue;
            }

            $remainingAmount = $originalAmount - $totalDiscount;
            if ($remainingAmount <= 0) {
                break;
            }

            $discountAmount = $this->calculate($userDiscount->discount, $remainingAmount);
            
            if ($discountAmount > 0) {
                $totalDiscount += $discountAmount;
                $appliedDiscounts[] = [
                    'user_discount' => $userDiscount,
                    'discount_amount' => $discountAmount,
                ];
            }
        }

        return [
            'total_discount' => $totalDiscount,
            'final_amount' => $originalAmount - $totalDiscount,
            'applied_discounts' => $appliedDiscounts,
        ];
    }

    /**
     * Sort discounts by stacking order
     */
    protected function sortDiscountsByStackingOrder(array $userDiscounts, array $stackingOrder): array
    {
        usort($userDiscounts, function ($a, $b) use ($stackingOrder) {
            $aOrder = $stackingOrder[$a->discount->type] ?? 999;
            $bOrder = $stackingOrder[$b->discount->type] ?? 999;
            
            return $aOrder <=> $bOrder;
        });

        return $userDiscounts;
    }
}
