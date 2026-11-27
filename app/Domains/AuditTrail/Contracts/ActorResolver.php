<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Contracts;

use App\Domains\AuditTrail\AuditActor;

/**
 * Extensible actor contract so authentication and future job contexts can be
 * integrated without the audit domain hardcoding their internals.
 */
interface ActorResolver
{
    public function resolve(): AuditActor;
}
