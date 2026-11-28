<?php

declare(strict_types=1);

namespace App\Frontend\Contracts;

/**
 * Frontend boundary for role assignments.
 *
 * Every read and write is scoped to the authoritative school context and the
 * authenticated actor. The UI uses the same permission service as the verified
 * /api/role-assignments routes; it never sends a client-supplied school_id.
 */
interface RoleAssignmentGateway
{
    public function canView(): bool;

    public function canManage(): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function assignments(): array;

    /**
     * @return list<array{value: string, label: string}>
     */
    public function roles(): array;

    /**
     * @return array<string, mixed>
     */
    public function assign(int $userId, string $role): array;

    public function remove(int $assignmentId): void;
}
