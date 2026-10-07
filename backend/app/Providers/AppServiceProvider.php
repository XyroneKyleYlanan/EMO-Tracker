<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

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
        // Tokens belonging to deactivated accounts are rejected on every route.
        Sanctum::authenticateAccessTokensUsing(
            fn ($accessToken, bool $isValid) => $isValid && $accessToken->tokenable?->is_active
        );

        // Ten tries a minute for each account on each device, so one person's
        // typos (or someone guessing one password) never lock everyone else out.
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');

            return Limit::perMinute(10)
                ->by(Str::lower(is_string($email) ? $email : '').'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Too many login attempts. Wait a minute, then try again.',
                ], 429, $headers));
        });
    }
}
