<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Domains\Authorization\Actions\AssignSchoolRole;
use App\Domains\Authorization\Actions\ListSchoolRoleAssignments;
use App\Domains\Authorization\Actions\RemoveSchoolRole;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Frontend\Contracts\RoleAssignmentGateway;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-assignment adapter backed by the verified domain actions.
 *
 * Permission checks are advisory in the UI only; the actions and the API
 * routes remain the authoritative boundary.
 */
final class DomainRoleAssignmentGateway implements RoleAssignmentGateway
{
    public function __construct(
        private readonly SchoolContext $context,
        private readonly SchoolPermissions $permissions,
        private readonly ListSchoolRoleAssignments $listAction,
        private readonly AssignSchoolRole $assignAction,
        private readonly RemoveSchoolRole $removeAction,
    ) {}

    public function canView(): bool
    {
        return $this->permissions->has($this->actor(), $this->school(), 'roles.view');
    }

    public function canManage(): bool
    {
        return $this->permissions->has($this->actor(), $this->school(), 'roles.manage');
    }

    public function assignments(): array
    {
        if (! $this->canView()) {
            return [];
        }

        /** @var list<array<string, mixed>> $assignments */
        $assignments = $this->listAction->execute($this->actor(), $this->school());

        return $assignments;
    }

    public function roles(): array
    {
        /** @var array<string, array<string, mixed>> $roles */
        $roles = (array) config('authorization.roles', []);
        $options = [];

        foreach ($roles as $value => $details) {
            $options[] = [
                'value' => (string) $value,
                'label' => (string) ($details['label'] ?? $value),
            ];
        }

        return $options;
    }

    public function assign(int $userId, string $role): array
    {
        $assignment = $this->assignAction->execute($this->actor(), $this->school(), $userId, $role);

        return [
            'id' => (int) $assignment->id,
            'school_id' => (int) $assignment->school_id,
            'user_id' => (int) $assignment->user_id,
            'role' => (string) $assignment->role,
            'source' => (string) $assignment->source,
            'created_at' => $assignment->created_at->toIso8601String(),
        ];
    }

    public function remove(int $assignmentId): void
    {
        $this->removeAction->execute($this->actor(), $this->school(), $assignmentId);
    }

    private function school(): School
    {
        return $this->context->assertCurrent();
    }

    private function actor(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication is required.');
        }

        return $user;
    }
}
