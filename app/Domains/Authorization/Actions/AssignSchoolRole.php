<?php

declare(strict_types=1);

namespace App\Domains\Authorization\Actions;

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Assigns a configured role to a user in one authorised school.
 *
 * An actor may not grant a role containing permissions they do not already
 * hold. This is a foundation control, not a full delegation hierarchy.
 */
final readonly class AssignSchoolRole
{
    public function __construct(private SchoolPermissions $permissions) {}

    public function execute(
        User $actor,
        School $school,
        int $userId,
        string $role,
        string $source = 'domain',
    ): RoleAssignment {
        $this->assertManager($actor, $school);
        $this->assertRole($role);
        $this->assertSource($source);

        /** @var User $target */
        $target = User::query()->findOrFail($userId);

        if (! $target->schools()->whereKey($school->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['The user is not a member of the authorised school.'],
            ]);
        }

        $missing = array_diff(
            $this->permissions->rolePermissions($role),
            array_keys($this->permissions->effectivePermissions($actor, $school)),
        );

        if ($missing !== []) {
            throw new AuthorizationException('A role cannot grant permissions the actor does not hold.');
        }

        if (RoleAssignment::query()
            ->forSchool($school)
            ->forUser($target)
            ->where('role', $role)
            ->exists()) {
            throw ValidationException::withMessages([
                'role' => ['This role is already assigned to the user in this school.'],
            ]);
        }

        /** @var RoleAssignment $assignment */
        $assignment = RoleAssignment::query()->forceCreate([
            'school_id' => (int) $school->id,
            'user_id' => (int) $target->id,
            'role' => $role,
            'source' => $source,
            'created_at' => Carbon::now(),
        ]);

        return $assignment;
    }

    private function assertManager(User $actor, School $school): void
    {
        if (! $this->permissions->has($actor, $school, 'roles.manage')) {
            throw new AuthorizationException('The actor cannot manage role assignments.');
        }
    }

    private function assertRole(string $role): void
    {
        /** @var array<string, mixed> $roles */
        $roles = (array) config('authorization.roles', []);

        if (! array_key_exists($role, $roles)) {
            throw ValidationException::withMessages([
                'role' => ['The selected role is not configured.'],
            ]);
        }
    }

    private function assertSource(string $source): void
    {
        /** @var array<string, mixed> $sources */
        $sources = (array) config('authorization.assignment_sources', []);

        if (! array_key_exists($source, $sources)) {
            throw ValidationException::withMessages([
                'source' => ['The selected assignment source is not configured.'],
            ]);
        }
    }
}
