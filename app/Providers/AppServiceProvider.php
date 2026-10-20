<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $key = fn (Request $r) => $r->user()?->id ?: $r->ip();

        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(60)->by($key($r)));
        RateLimiter::for('openlibrary', fn (Request $r) => Limit::perMinute(20)->by($key($r)));
        RateLimiter::for('mutations', fn (Request $r) => Limit::perMinute(120)->by($key($r)));
    }
}
