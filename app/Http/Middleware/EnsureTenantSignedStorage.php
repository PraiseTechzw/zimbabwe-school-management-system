<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\School\Context\SchoolContext;
use App\Models\User;
use App\Services\TenantFileStorage;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a signature that was issued for the authenticated school context.
 *
 * This runs after authentication and ResolveSchoolContext. It never trusts a
 * client-supplied school identifier as membership proof; the identifier in the
 * signed URL must match the server-selected school.
 */
final class EnsureTenantSignedStorage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication is required.');
        }

        $school = app(SchoolContext::class)->current();

        if ($school === null) {
            abort(Response::HTTP_FORBIDDEN, 'No authorised school context is available.');
        }

        if (! $request->hasValidSignature()) {
            abort(Response::HTTP_FORBIDDEN, 'A valid signed file URL is required.');
        }

        $signedSchoolId = $request->query(TenantFileStorage::TENANT_PARAMETER);

        if (! is_scalar($signedSchoolId)
            || ! ctype_digit((string) $signedSchoolId)
            || (int) $signedSchoolId !== (int) $school->id) {
            abort(Response::HTTP_FORBIDDEN, 'The signed URL belongs to another school.');
        }

        $isUpload = $request->isMethod('PUT');
        $signedUpload = filter_var($request->query('upload', false), FILTER_VALIDATE_BOOLEAN);

        if ($signedUpload !== $isUpload) {
            abort(Response::HTTP_FORBIDDEN, 'The signed URL cannot be used for this operation.');
        }

        $path = $request->route('path');

        if (! is_string($path)) {
            abort(Response::HTTP_FORBIDDEN, 'A valid file path is required.');
        }

        try {
            TenantFileStorage::assertTenantPath((int) $school->id, $path);
        } catch (AuthorizationException) {
            abort(Response::HTTP_FORBIDDEN, 'The file path is outside the authorised school.');
        }

        return $next($request);
    }
}
