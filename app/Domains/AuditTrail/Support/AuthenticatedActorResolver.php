<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Support;

use App\Domains\AuditTrail\AuditActor;
use App\Domains\AuditTrail\Contracts\ActorResolver;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Default actor contract implementation using the existing auth guard.
 *
 * When no user is available the actor is explicitly recorded as the system,
 * never inferred from client input.
 */
final class AuthenticatedActorResolver implements ActorResolver
{
    public function resolve(): AuditActor
    {
        $user = Auth::user();

        return $user instanceof User ? AuditActor::user($user) : AuditActor::system();
    }
}
