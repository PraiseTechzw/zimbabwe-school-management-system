<?php

declare(strict_types=1);

namespace App\Domains\Authorization\Actions;

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class RemoveSchoolRole
{
    public function __construct(private SchoolPermissions $permissions) {}

    public function execute(User $actor, School $school, int $assignmentId): void
    {
        if (! $this->permissions->has($actor, $school, 'roles.manage')) {
            throw new AuthorizationException('The actor cannot manage role assignments.');
        }

        $assignment = RoleAssignment::query()
            ->forSchool($school)
            ->whereKey($assignmentId)
            ->firstOrFail();

        $assignment->delete();
    }
}
