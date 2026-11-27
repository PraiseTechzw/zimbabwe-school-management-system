<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side permission gate for routes that opt into authorization.
 *
 * It never reads a school or role from client input. The school comes from
 * ResolveSchoolContext and the actor comes from the authenticated user.
 */
final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication is required.');
        }

        $school = app(SchoolContext::class)->current();

        if ($school === null) {
            abort(Response::HTTP_FORBIDDEN, 'No authorised school context is available.');
        }

        if (! app(SchoolPermissions::class)->has($user, $school, $permission)) {
            abort(Response::HTTP_FORBIDDEN, 'The user does not hold the required permission.');
        }

        return $next($request);
    }
}
