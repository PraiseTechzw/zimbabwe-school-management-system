<?php

declare(strict_types=1);

namespace App\Domains\School\Context;

use App\Domains\School\Models\School;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Request-scoped school context.
 *
 * The context is populated only by server-side authorisation (see
 * ResolveSchoolContext). Client-supplied identifiers are never trusted as
 * proof of access; they are checked against the authenticated user's
 * memberships before they can select a context.
 */
final class SchoolContext
{
    private ?School $school = null;

    public function set(School $school): void
    {
        $this->school = $school;
    }

    public function clear(): void
    {
        $this->school = null;
    }

    public function current(): ?School
    {
        return $this->school;
    }

    public function id(): ?int
    {
        return $this->school === null ? null : (int) $this->school->id;
    }

    /**
     * @throws AuthorizationException when no school has been authorised
     */
    public function assertCurrent(): School
    {
        if ($this->school === null) {
            throw new AuthorizationException('No school context has been authorised for this request.');
        }

        return $this->school;
    }

    public function allows(School|int $school): bool
    {
        $id = $school instanceof School ? (int) $school->id : $school;

        return $this->id() === $id;
    }

    /**
     * @throws AuthorizationException when the school is outside the authorised context
     */
    public function assertAllows(School|int $school): void
    {
        if (! $this->allows($school)) {
            throw new AuthorizationException('The requested school is outside the authorised school context.');
        }
    }
}
