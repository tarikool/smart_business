<?php

namespace Soluta\Subscription;

use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/soulbscription.php', 'soulbscription');

        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        if (! config('soulbscription.database.cancel_migrations_autoloading')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->publishes([
            __DIR__ . '/../config/soulbscription.php' => config_path('soulbscription.php'),
        ], 'soulbscription-config');

    }
}
