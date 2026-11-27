<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the single authorised school for a request.
 *
 * A client may only select from schools the authenticated user already
 * belongs to. Supplying an unauthorised school ID fails; it never widens the
 * user's access.
 */
final class ResolveSchoolContext
{
    public function __construct(private readonly SchoolContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication is required.');
        }

        $schoolIds = $user->schools()->pluck('schools.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($schoolIds === []) {
            abort(Response::HTTP_FORBIDDEN, 'The user has no school access.');
        }

        $requested = $this->requestedSchoolId($request);

        if ($requested === null && count($schoolIds) > 1) {
            abort(Response::HTTP_BAD_REQUEST, 'A school must be selected when the user belongs to more than one school.');
        }

        $schoolId = $requested ?? $schoolIds[0];

        if (! in_array($schoolId, $schoolIds, true)) {
            abort(Response::HTTP_FORBIDDEN, 'The requested school is not authorised for this user.');
        }

        /** @var School $school */
        $school = School::query()->findOrFail($schoolId);

        $this->context->set($school);

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }

    private function requestedSchoolId(Request $request): ?int
    {
        /** @var array<int, string> $supplied */
        $supplied = [];

        foreach ([$request->header('X-School-Id'), $request->input('school_id')] as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (! is_string($value) && ! is_int($value)) {
                abort(Response::HTTP_BAD_REQUEST, 'The school identifier must be a single integer.');
            }

            $supplied[] = trim((string) $value);
        }

        $supplied = array_values(array_unique($supplied));

        if ($supplied === []) {
            return null;
        }

        if (count($supplied) > 1) {
            abort(Response::HTTP_BAD_REQUEST, 'Conflicting school identifiers were supplied.');
        }

        if (! ctype_digit($supplied[0])) {
            abort(Response::HTTP_BAD_REQUEST, 'The school identifier must be an integer.');
        }

        return (int) $supplied[0];
    }
}
