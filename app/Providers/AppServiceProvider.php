<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addHours(1));      // Access token
        Passport::refreshTokensExpireIn(now()->addDays(1));  // Refresh token
    }
}
