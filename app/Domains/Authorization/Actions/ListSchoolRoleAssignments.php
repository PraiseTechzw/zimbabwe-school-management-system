<?php

declare(strict_types=1);

namespace App\Domains\Authorization\Actions;

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class ListSchoolRoleAssignments
{
    public function __construct(private SchoolPermissions $permissions) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(User $actor, School $school): array
    {
        if (! $this->permissions->has($actor, $school, 'roles.view')) {
            throw new AuthorizationException('The actor cannot view role assignments.');
        }

        /** @var array<int, array<string, mixed>> $assignments */
        $assignments = RoleAssignment::query()
            ->forSchool($school)
            ->orderBy('id')
            ->get()
            ->map(fn (RoleAssignment $assignment): array => [
                'id' => (int) $assignment->id,
                'school_id' => (int) $assignment->school_id,
                'user_id' => (int) $assignment->user_id,
                'role' => (string) $assignment->role,
                'source' => (string) $assignment->source,
                'created_at' => $assignment->created_at->toIso8601String(),
            ])
            ->all();

        return $assignments;
    }
}
