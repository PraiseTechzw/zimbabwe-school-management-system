<?php

declare(strict_types=1);

namespace App\Domains\Auth\Actions;

use Illuminate\Support\Facades\Auth;

/**
 * Ends the authenticated guard session. HTTP-level session invalidation and
 * CSRF-token rotation remain the controller's responsibility.
 */
final class LogoutUser
{
    public function handle(): void
    {
        Auth::guard('web')->logout();
    }
}
