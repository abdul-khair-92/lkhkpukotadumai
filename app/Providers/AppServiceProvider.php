<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    /**
     * Bootstrap any application services.
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $host = request()->getHost();
        $isIpOrLocal = $host === 'localhost' || 
                       $host === '127.0.0.1' || 
                       str_ends_with($host, '.test') || 
                       str_ends_with($host, '.local') || 
                       filter_var($host, FILTER_VALIDATE_IP);

        $isSecureRequest = request()->secure() ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https')) ||
            (request()->header('X-Forwarded-Proto') === 'https');

        if ($isSecureRequest || !$isIpOrLocal) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
