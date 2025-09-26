<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use CsvImage\UserDiscounts\Services\DiscountService;
use CsvImage\UserDiscounts\Models\Discount;
use CsvImage\UserDiscounts\Models\UserDiscount;

class DiscountController extends Controller
{
    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * Display discount management page
     */
    public function index()
    {
        $discounts = Discount::with('userDiscounts')->paginate(10);
        $userDiscounts = UserDiscount::with(['discount', 'user'])
            ->where('user_id', auth()->id())
            ->paginate(10);

        return view('discounts.index', compact('discounts', 'userDiscounts'));
    }

    /**
     * Create a new discount
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:discounts,code',
                'type' => 'required|in:percentage,fixed,buy_x_get_y',
                'value' => 'required|numeric|min:0',
                'min_order_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'per_user_limit' => 'nullable|integer|min:1',
                'starts_at' => 'required|date|after_or_equal:today',
                'expires_at' => 'nullable|date|after:starts_at',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $discount = Discount::create($request->all());

            Log::info('Discount created successfully', [
                'discount_id' => $discount->id,
                'code' => $discount->code,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Discount created successfully',
                'discount' => $discount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create discount', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign discount to user
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'discount_id' => 'required|integer|exists:discounts,id',
                'max_usage' => 'nullable|integer|min:1',
                'expires_at' => 'nullable|date|after:today',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userDiscount = $this->discountService->assign(
                $request->user_id,
                $request->discount_id,
                $request->max_usage,
                $request->expires_at ? new \DateTime($request->expires_at) : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Discount assigned successfully',
                'user_discount' => $userDiscount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign discount', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke discount from user
     */
    public function revoke(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'discount_id' => 'required|integer|exists:discounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $this->discountService->revoke($request->user_id, $request->discount_id);

            return response()->json([
                'success' => true,
                'message' => 'Discount revoked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to revoke discount', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user is eligible for discount
     */
    public function eligible(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'discount_id' => 'required|integer|exists:discounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eligible = $this->discountService->eligibleFor($request->user_id, $request->discount_id);

            return response()->json([
                'success' => true,
                'eligible' => $eligible
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check discount eligibility', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check eligibility: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply discount to amount
     */
    public function apply(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'discount_id' => 'required|integer|exists:discounts,id',
                'original_amount' => 'required|numeric|min:0',
                'order_reference' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->discountService->apply(
                $request->user_id,
                $request->discount_id,
                $request->original_amount,
                $request->order_reference
            );

            return response()->json([
                'success' => true,
                'message' => 'Discount applied successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to apply discount', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's available discounts
     */
    public function userDiscounts(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id', auth()->id());
            $discounts = $this->discountService->getUserDiscounts($userId);

            return response()->json([
                'success' => true,
                'discounts' => $discounts
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user discounts', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get user discounts: ' . $e->getMessage()
            ], 500);
        }
    }
}
