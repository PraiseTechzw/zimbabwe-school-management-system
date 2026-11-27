<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the authentication surface without changing shared routing files.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', static function (Request $request): Limit {
            $email = $request->input('email');
            $identity = is_string($email) ? mb_strtolower(trim($email)) : '';

            return Limit::perMinute(10)->by($request->ip().'|'.$identity);
        });

        // Independent per-IP backstop for email spraying from a single source.
        RateLimiter::for('login-ip', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));

        Route::middleware('web')->group(base_path('routes/auth.php'));
    }
}
