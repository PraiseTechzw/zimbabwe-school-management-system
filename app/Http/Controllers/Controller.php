<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /**
     * Explicit actor check for controllers whose actions can be reached
     * outside the HTTP middleware stack. The school is always the resolved
     * server-side context, never client input.
     */
    protected function authorizeSchoolPermission(School $school, string $permission): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication is required.');
        }

        app(SchoolPermissions::class)->authorize($user, $school, $permission);
    }

    /**
     * Non-throwing check for view data that must not leak to a user without
     * the corresponding action permission.
     */
    protected function hasSchoolPermission(School $school, string $permission): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && app(SchoolPermissions::class)->has($user, $school, $permission);
    }
}
