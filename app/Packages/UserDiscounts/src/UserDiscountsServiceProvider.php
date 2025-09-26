<?php

namespace CsvImage\UserDiscounts;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use CsvImage\UserDiscounts\Events\DiscountAssigned;
use CsvImage\UserDiscounts\Events\DiscountRevoked;
use CsvImage\UserDiscounts\Events\DiscountApplied;

class UserDiscountsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/user-discounts.php', 'user-discounts'
        );
    }

    public function boot()
    {
        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'user-discounts-migrations');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/user-discounts.php' => config_path('user-discounts.php'),
        ], 'user-discounts-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register event listeners
        $this->registerEventListeners();
    }

    protected function registerEventListeners()
    {
        // Event listeners can be registered here if needed
    }
}
