<?php

namespace CsvImage\UserDiscounts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use CsvImage\UserDiscounts\Models\Discount;
use CsvImage\UserDiscounts\Models\UserDiscount;

class DiscountRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Discount $discount,
        public UserDiscount $userDiscount,
        public int $userId
    ) {}
}
