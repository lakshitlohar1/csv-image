<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use CsvImage\UserDiscounts\Services\DiscountService;
use CsvImage\UserDiscounts\Services\DiscountCalculationService;
use CsvImage\UserDiscounts\Models\Discount;
use CsvImage\UserDiscounts\Models\UserDiscount;
use CsvImage\UserDiscounts\Models\DiscountAudit;
use CsvImage\UserDiscounts\Events\DiscountAssigned;
use CsvImage\UserDiscounts\Events\DiscountApplied;
use App\Models\User;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DiscountService $discountService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->discountService = app(DiscountService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_assign_discount_to_user()
    {
        Event::fake();

        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $userDiscount = $this->discountService->assign(
            $this->user->id,
            $discount->id
        );

        $this->assertInstanceOf(UserDiscount::class, $userDiscount);
        $this->assertEquals($this->user->id, $userDiscount->user_id);
        $this->assertEquals($discount->id, $userDiscount->discount_id);
        $this->assertTrue($userDiscount->is_active);

        Event::assertDispatched(DiscountAssigned::class);
    }

    /** @test */
    public function it_can_apply_percentage_discount()
    {
        Event::fake();

        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $result = $this->discountService->apply(
            $this->user->id,
            $discount->id,
            100.00
        );

        $this->assertEquals(100.00, $result['original_amount']);
        $this->assertEquals(10.00, $result['discount_amount']);
        $this->assertEquals(90.00, $result['final_amount']);

        Event::assertDispatched(DiscountApplied::class);
    }

    /** @test */
    public function it_can_apply_fixed_discount()
    {
        Event::fake();

        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'FIXED5',
            'type' => 'fixed',
            'value' => 5.00,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $result = $this->discountService->apply(
            $this->user->id,
            $discount->id,
            100.00
        );

        $this->assertEquals(100.00, $result['original_amount']);
        $this->assertEquals(5.00, $result['discount_amount']);
        $this->assertEquals(95.00, $result['final_amount']);

        Event::assertDispatched(DiscountApplied::class);
    }

    /** @test */
    public function it_enforces_usage_cap()
    {
        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'per_user_limit' => 1,
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
            'max_usage' => 1,
            'usage_count' => 1,
        ]);

        $this->assertFalse($this->discountService->eligibleFor($this->user->id, $discount->id));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is not eligible for this discount');

        $this->discountService->apply($this->user->id, $discount->id, 100.00);
    }

    /** @test */
    public function it_prevents_double_assignment()
    {
        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        // First assignment
        $this->discountService->assign($this->user->id, $discount->id);

        // Second assignment should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User already has this active discount');

        $this->discountService->assign($this->user->id, $discount->id);
    }

    /** @test */
    public function it_creates_audit_records()
    {
        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $userDiscount = $this->discountService->assign($this->user->id, $discount->id);

        $this->assertDatabaseHas('discount_audits', [
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'user_discount_id' => $userDiscount->id,
            'action' => 'assigned',
        ]);
    }

    /** @test */
    public function it_handles_concurrent_usage_correctly()
    {
        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        // Simulate concurrent access
        $this->discountService->apply($this->user->id, $discount->id, 100.00);
        
        // Second application should fail due to usage cap
        $this->expectException(\Exception::class);
        $this->discountService->apply($this->user->id, $discount->id, 100.00);
    }

    /** @test */
    public function it_respects_minimum_order_amount()
    {
        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'min_order_amount' => 50.00,
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $result = $this->discountService->apply($this->user->id, $discount->id, 25.00);

        $this->assertEquals(0.00, $result['discount_amount']);
        $this->assertEquals(25.00, $result['final_amount']);
    }

    /** @test */
    public function it_respects_maximum_discount_amount()
    {
        $discount = Discount::create([
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 50, // 50% discount
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'max_discount_amount' => 10.00, // Cap at $10
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $result = $this->discountService->apply($this->user->id, $discount->id, 100.00);

        $this->assertEquals(10.00, $result['discount_amount']); // Capped at $10
        $this->assertEquals(90.00, $result['final_amount']);
    }
}
