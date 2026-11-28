<?php

declare(strict_types=1);

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

function rbacUser(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    return $user;
}

function rbacGrant(User $user, School $school, string $role): RoleAssignment
{
    /** @var RoleAssignment $assignment */
    $assignment = RoleAssignment::query()->forceCreate([
        'school_id' => (int) $school->id,
        'user_id' => (int) $user->id,
        'role' => $role,
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    return $assignment;
}

it('resolves permissions only from active role assignments in the authorised school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = rbacUser($school);
    $permissions = app(SchoolPermissions::class);

    expect($permissions->has($user, $school, 'academic_calendar.view'))->toBeFalse()
        ->and($permissions->has($user, $school, 'unknown.permission'))->toBeFalse();

    rbacGrant($user, $school, 'school_member');

    expect($permissions->has($user, $school, 'school.view'))->toBeTrue()
        ->and($permissions->has($user, $school, 'academic_calendar.view'))->toBeTrue()
        ->and($permissions->has($user, $school, 'academic_calendar.manage'))->toBeFalse()
        ->and($permissions->has($user, $school, 'roles.manage'))->toBeFalse();

    rbacGrant($user, $otherSchool, 'school_admin');

    expect($permissions->has($user, $otherSchool, 'roles.manage'))->toBeFalse();
});

it('honours config driven roles without schema changes', function (): void {
    config()->set('authorization.roles.audit_reviewer', [
        'label' => 'Audit reviewer',
        'permissions' => ['school.view', 'roles.view'],
    ]);

    $school = School::factory()->create();
    $user = rbacUser($school);
    rbacGrant($user, $school, 'audit_reviewer');

    expect(app(SchoolPermissions::class)->has($user, $school, 'roles.view'))->toBeTrue();
});

it('denies role assignment access when the authenticated user has no role', function (): void {
    $school = School::factory()->create();

    $this->actingAs(rbacUser($school))
        ->getJson('/api/role-assignments')
        ->assertForbidden();
});

it('lists only assignments in the server resolved school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $viewer = rbacUser($school);
    rbacGrant($viewer, $school, 'role_viewer');

    $outsideUser = rbacUser($otherSchool);
    rbacGrant($outsideUser, $otherSchool, 'role_viewer');

    $response = $this->actingAs($viewer)->getJson('/api/role-assignments')->assertOk();

    /** @var array<int, array<string, mixed>> $payload */
    $payload = (array) $response->json('role_assignments');

    expect($response->json('school_id'))->toBe($school->id)
        ->and($payload)->toHaveCount(1)
        ->and($payload[0]['user_id'])->toBe($viewer->id)
        ->and($payload[0]['role'])->toBe('role_viewer');
});

it('rejects client supplied school identifiers on role endpoints', function (): void {
    $school = School::factory()->create();
    $admin = rbacUser($school);
    rbacGrant($admin, $school, 'school_admin');

    // An authorised identifier is used so the prohibited-field rule is
    // exercised; an unauthorised identifier is rejected earlier by school context.
    $this->actingAs($admin)
        ->getJson('/api/role-assignments?school_id='.$school->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');

    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'role' => 'school_member',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');
});

it('lets an authorised manager assign a configured role to a school member', function (): void {
    $school = School::factory()->create();
    $admin = rbacUser($school);
    rbacGrant($admin, $school, 'school_admin');
    $target = rbacUser($school);

    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_editor',
        ])
        ->assertCreated()
        ->assertJsonPath('role_assignment.school_id', $school->id)
        ->assertJsonPath('role_assignment.user_id', $target->id)
        ->assertJsonPath('role_assignment.role', 'school_editor')
        ->assertJsonPath('role_assignment.source', 'domain');

    expect(RoleAssignment::query()
        ->forSchool($school)
        ->forUser($target)
        ->where('role', 'school_editor')
        ->count())->toBe(1);
});

it('prevents a manager from granting permissions they do not hold', function (): void {
    config()->set('authorization.roles.limited_manager', [
        'label' => 'Limited manager',
        'permissions' => [
            'school.view',
            'school_identifiers.view',
            'school_settings.view',
            'academic_calendar.view',
            'academic_structure.view',
            'roles.manage',
        ],
    ]);

    $school = School::factory()->create();
    $actor = rbacUser($school);
    rbacGrant($actor, $school, 'limited_manager');
    $target = rbacUser($school);

    $this->actingAs($actor)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_editor',
        ])
        ->assertForbidden();

    expect(RoleAssignment::query()->forUser($target)->count())->toBe(0);

    $this->actingAs($actor)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_member',
        ])
        ->assertCreated();

    expect(RoleAssignment::query()
        ->forSchool($school)
        ->forUser($target)
        ->where('role', 'school_member')
        ->count())->toBe(1);
});

it('rejects duplicate assignments, unknown roles and users outside the school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $admin = rbacUser($school);
    rbacGrant($admin, $school, 'school_admin');
    $target = rbacUser($school);
    $outsideUser = rbacUser($otherSchool);

    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'not_a_role',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');

    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $outsideUser->id,
            'role' => 'school_member',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('user_id');

    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_member',
        ])
        ->assertCreated();

    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_member',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');

    expect(RoleAssignment::query()->forUser($outsideUser)->count())->toBe(0);
});

it('revokes access immediately when a role assignment is removed', function (): void {
    $school = School::factory()->create();
    $admin = rbacUser($school);
    rbacGrant($admin, $school, 'school_admin');
    $target = rbacUser($school);
    $assignment = rbacGrant($target, $school, 'role_viewer');

    $this->actingAs($target)->getJson('/api/role-assignments')->assertOk();

    $this->actingAs($admin)
        ->deleteJson('/api/role-assignments/'.$assignment->id)
        ->assertNoContent();

    expect(RoleAssignment::query()->whereKey($assignment->id)->exists())->toBeFalse();

    $this->actingAs($target)->getJson('/api/role-assignments')->assertForbidden();
});

it('cannot remove an assignment outside the authorised school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $admin = rbacUser($school);
    rbacGrant($admin, $school, 'school_admin');
    $outsideUser = rbacUser($otherSchool);
    $outsideAssignment = rbacGrant($outsideUser, $otherSchool, 'school_member');

    $this->actingAs($admin)
        ->deleteJson('/api/role-assignments/'.$outsideAssignment->id)
        ->assertNotFound();

    expect(RoleAssignment::query()->whereKey($outsideAssignment->id)->exists())->toBeTrue();
});

it('provides a trusted cli bootstrap path for the first assignment', function (): void {
    $school = School::factory()->create();
    $user = rbacUser($school);

    Artisan::call('roles:assign', [
        '--school' => (string) $school->id,
        '--user' => (string) $user->id,
        '--role' => 'school_admin',
    ]);

    expect(Artisan::output())->toContain('Assigned school_admin')
        ->and(RoleAssignment::query()
            ->forSchool($school)
            ->forUser($user)
            ->where('role', 'school_admin')
            ->where('source', 'cli')
            ->count())->toBe(1);
});
