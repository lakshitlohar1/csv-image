<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use CsvImage\UserDiscounts\Models\Discount;
use CsvImage\UserDiscounts\Models\UserDiscount;
use CsvImage\UserDiscounts\Events\DiscountAssigned;
use CsvImage\UserDiscounts\Events\DiscountApplied;
use App\Models\User;

class DiscountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function complete_discount_workflow_works_correctly()
    {
        Event::fake();

        // 1. Create a discount
        $discount = Discount::create([
            'name' => 'Welcome Discount',
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'per_user_limit' => 1,
        ]);

        // 2. Assign discount to user
        $this->actingAs($this->user)
            ->post('/discounts/assign', [
                'user_id' => $this->user->id,
                'discount_id' => $discount->id,
                'max_usage' => 1,
            ])
            ->assertJson(['success' => true]);

        Event::assertDispatched(DiscountAssigned::class);

        // 3. Check if user is eligible
        $this->actingAs($this->user)
            ->post('/discounts/eligible', [
                'user_id' => $this->user->id,
                'discount_id' => $discount->id,
            ])
            ->assertJson(['success' => true, 'eligible' => true]);

        // 4. Apply discount
        $this->actingAs($this->user)
            ->post('/discounts/apply', [
                'user_id' => $this->user->id,
                'discount_id' => $discount->id,
                'original_amount' => 100.00,
                'order_reference' => 'ORDER-123',
            ])
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'original_amount',
                    'discount_amount',
                    'final_amount',
                    'discount_code',
                ]
            ]);

        Event::assertDispatched(DiscountApplied::class);

        // 5. Verify usage count increased
        $userDiscount = UserDiscount::where('user_id', $this->user->id)
            ->where('discount_id', $discount->id)
            ->first();

        $this->assertEquals(1, $userDiscount->usage_count);
        $this->assertTrue($userDiscount->isUsageLimitReached());
    }

    /** @test */
    public function expired_discounts_are_excluded()
    {
        $expiredDiscount = Discount::create([
            'name' => 'Expired Discount',
            'code' => 'EXPIRED10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->subDays(1),
            'is_active' => true,
        ]);

        $userDiscount = UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $expiredDiscount->id,
            'assigned_at' => now()->subDays(30),
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post('/discounts/eligible', [
                'user_id' => $this->user->id,
                'discount_id' => $expiredDiscount->id,
            ])
            ->assertJson(['success' => true, 'eligible' => false]);

        $this->actingAs($this->user)
            ->post('/discounts/apply', [
                'user_id' => $this->user->id,
                'discount_id' => $expiredDiscount->id,
                'original_amount' => 100.00,
            ])
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function inactive_discounts_are_excluded()
    {
        $inactiveDiscount = Discount::create([
            'name' => 'Inactive Discount',
            'code' => 'INACTIVE10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->post('/discounts/assign', [
                'user_id' => $this->user->id,
                'discount_id' => $inactiveDiscount->id,
            ])
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function usage_caps_are_enforced()
    {
        $discount = Discount::create([
            'name' => 'Limited Discount',
            'code' => 'LIMITED10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_limit' => 1,
            'usage_count' => 1,
        ]);

        $this->actingAs($this->user)
            ->post('/discounts/assign', [
                'user_id' => $this->user->id,
                'discount_id' => $discount->id,
            ])
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function discount_creation_works_with_validation()
    {
        $this->actingAs($this->user)
            ->post('/discounts', [
                'name' => 'Test Discount',
                'code' => 'TEST10',
                'type' => 'percentage',
                'value' => 10,
                'starts_at' => now()->format('Y-m-d\TH:i'),
                'expires_at' => now()->addDays(30)->format('Y-m-d\TH:i'),
                'is_active' => true,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('discounts', [
            'name' => 'Test Discount',
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
        ]);
    }

    /** @test */
    public function discount_creation_fails_with_invalid_data()
    {
        $this->actingAs($this->user)
            ->post('/discounts', [
                'name' => '', // Invalid: empty name
                'code' => 'TEST10',
                'type' => 'invalid_type', // Invalid type
                'value' => -10, // Invalid: negative value
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function user_can_view_their_discounts()
    {
        $discount = Discount::create([
            'name' => 'User Discount',
            'code' => 'USER10',
            'type' => 'percentage',
            'value' => 10,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        UserDiscount::create([
            'user_id' => $this->user->id,
            'discount_id' => $discount->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get('/discounts/user-discounts?user_id=' . $this->user->id)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'discounts' => [
                    '*' => [
                        'id',
                        'user_id',
                        'discount_id',
                        'usage_count',
                        'discount' => [
                            'id',
                            'name',
                            'code',
                            'type',
                            'value',
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function discount_application_is_deterministic_and_idempotent()
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

        // First application
        $response1 = $this->actingAs($this->user)
            ->post('/discounts/apply', [
                'user_id' => $this->user->id,
                'discount_id' => $discount->id,
                'original_amount' => 100.00,
            ])
            ->assertJson(['success' => true]);

        $result1 = $response1->json('result');

        // Second application should fail due to usage cap
        $this->actingAs($this->user)
            ->post('/discounts/apply', [
                'user_id' => $this->user->id,
                'discount_id' => $discount->id,
                'original_amount' => 100.00,
            ])
            ->assertJson(['success' => false]);

        // Verify the result is deterministic
        $this->assertEquals(100.00, $result1['original_amount']);
        $this->assertEquals(10.00, $result1['discount_amount']);
        $this->assertEquals(90.00, $result1['final_amount']);
    }
}
