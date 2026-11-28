<?php

declare(strict_types=1);

namespace App\Domains\Authorization\Services;

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;

/**
 * Resolves permissions only from active role assignments in one school.
 *
 * Users never receive permissions directly. Membership in a school and an
 * authenticatable account are also required, so a stale assignment cannot
 * grant access after removal and a suspended session cannot keep authority.
 */
final class SchoolPermissions
{
    public function has(User $user, School $school, string $permission): bool
    {
        if (! $user->canAuthenticate()) {
            return false;
        }

        if (! array_key_exists($permission, $this->permissions())) {
            return false;
        }

        if (! $user->schools()->whereKey($school->id)->exists()) {
            return false;
        }

        return array_key_exists($permission, $this->effectivePermissions($user, $school));
    }

    /**
     * @return array<string, true>
     */
    public function effectivePermissions(User $user, School $school): array
    {
        if (! $user->canAuthenticate()) {
            return [];
        }

        /** @var array<int, string> $roles */
        $roles = RoleAssignment::query()
            ->forSchool($school)
            ->forUser($user)
            ->pluck('role')
            ->map(fn (mixed $role): string => (string) $role)
            ->all();

        $effective = [];

        foreach ($roles as $role) {
            foreach ($this->rolePermissions($role) as $permission) {
                $effective[$permission] = true;
            }
        }

        return $effective;
    }

    /**
     * @return array<int, string>
     */
    public function rolePermissions(string $role): array
    {
        /** @var array<int, string> $permissions */
        $permissions = (array) config('authorization.roles.'.$role.'.permissions', []);

        if (in_array('*', $permissions, true)) {
            return array_keys($this->permissions());
        }

        return array_values(array_filter(
            $permissions,
            fn (string $permission): bool => array_key_exists($permission, $this->permissions()),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function permissions(): array
    {
        /** @var array<string, mixed> $permissions */
        $permissions = (array) config('authorization.permissions', []);

        return $permissions;
    }
}
