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
        
        // Cek apakah ini diakses dari komputer lokal atau IP address
        $isLocal = $host === 'localhost' ||
                   $host === '127.0.0.1' ||
                   str_ends_with($host, '.test') ||
                   str_ends_with($host, '.local') ||
                   filter_var($host, FILTER_VALIDATE_IP);

        // Jika BUKAN lokal (berarti domain asli/online), paksa HTTPS
        if (!$isLocal) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }


}
