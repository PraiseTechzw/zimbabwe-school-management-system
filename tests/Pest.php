<?php

declare(strict_types=1);

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Grant a configured role for one school in authorization test fixtures.
 */
function grantSchoolRole(User $user, School $school, string $role = 'school_member'): void
{
    RoleAssignment::factory()
        ->for($school)
        ->for($user, 'user')
        ->create(['role' => $role]);
}
