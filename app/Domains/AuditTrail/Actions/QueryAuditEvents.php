<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Actions;

use App\Domains\AuditTrail\AuditEventQuery;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\School\Models\School;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-only, server-side audit retrieval.
 *
 * No HTTP route is exposed in this slice because role and permission
 * enforcement has not been implemented. Callers must already hold an
 * authorised School model.
 */
final class QueryAuditEvents
{
    public function execute(School $school, AuditEventQuery $query): LengthAwarePaginator
    {
        $builder = AuditEvent::query()
            ->forSchool($school)
            ->when($query->action !== null, fn ($builder) => $builder->where('action', $query->action))
            ->when($query->category !== null, fn ($builder) => $builder->where('category', $query->category))
            ->when($query->resourceType !== null, fn ($builder) => $builder->where('resource_type', $query->resourceType))
            ->when($query->resourceId !== null, fn ($builder) => $builder->where('resource_id', $query->resourceId))
            ->when($query->actorId !== null, fn ($builder) => $builder->where('actor_id', $query->actorId))
            ->when($query->from !== null, fn ($builder) => $builder->where('occurred_at', '>=', $query->from.' 00:00:00'))
            ->when($query->to !== null, fn ($builder) => $builder->where('occurred_at', '<=', $query->to.' 23:59:59'))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        return $builder->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
