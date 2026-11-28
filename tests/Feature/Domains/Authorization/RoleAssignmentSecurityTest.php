<?php

declare(strict_types=1);

use App\Domains\Auth\Enums\AccountStatus;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function securityRbacUser(School $school, ?string $role = null, bool $suspended = false): User
{
    $user = $suspended
        ? User::factory()->suspended()->create()
        : User::factory()->create();

    $user->schools()->attach($school);

    if ($role !== null) {
        securityRbacGrant($user, $school, $role);
    }

    return $user;
}

function securityRbacGrant(User $user, School $school, string $role): RoleAssignment
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

it('rejects anonymous role assignment requests on every endpoint', function (): void {
    $school = School::factory()->create();

    $this->getJson('/api/role-assignments')->assertUnauthorized();
    $this->postJson('/api/role-assignments', [
        'user_id' => User::factory()->create()->id,
        'role' => 'school_member',
    ])->assertUnauthorized();
    $this->deleteJson('/api/role-assignments/1')->assertUnauthorized();

    expect(RoleAssignment::query()->forSchool($school)->count())->toBe(0);
});

it('denies role writes to viewers without changing assignments', function (): void {
    $school = School::factory()->create();
    $viewer = securityRbacUser($school, 'role_viewer');
    $target = securityRbacUser($school);
    $targetAssignment = securityRbacGrant($target, $school, 'school_member');
    $before = RoleAssignment::query()->forSchool($school)->count();

    $this->actingAs($viewer)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_editor',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->deleteJson('/api/role-assignments/'.$targetAssignment->id)
        ->assertForbidden();

    expect(RoleAssignment::query()->forSchool($school)->count())->toBe($before)
        ->and(RoleAssignment::query()->whereKey($targetAssignment->id)->exists())->toBeTrue();
});

it('stops suspended or disabled accounts from using an existing session', function (string $status): void {
    $school = School::factory()->create();
    $admin = $status === 'suspended'
        ? securityRbacUser($school, 'school_admin', true)
        : securityRbacUser($school, 'school_admin');

    if ($status === 'disabled') {
        $admin->status = AccountStatus::Disabled;
        $admin->save();
    }

    $target = securityRbacUser($school);

    $this->actingAs($admin)->getJson('/api/role-assignments')->assertForbidden();
    $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_member',
        ])
        ->assertForbidden();
    $this->actingAs($admin)->getJson('/api/academic-years')->assertForbidden();

    expect(RoleAssignment::query()->forUser($target)->count())->toBe(0);
})->with(['suspended', 'disabled']);

it('ignores client supplied assignment sources', function (): void {
    $school = School::factory()->create();
    $admin = securityRbacUser($school, 'school_admin');
    $target = securityRbacUser($school);

    $response = $this->actingAs($admin)
        ->postJson('/api/role-assignments', [
            'user_id' => $target->id,
            'role' => 'school_member',
            'source' => 'cli',
        ])
        ->assertCreated();

    expect($response->json('role_assignment.source'))->toBe('domain')
        ->and((int) RoleAssignment::query()
            ->forSchool($school)
            ->forUser($target)
            ->where('role', 'school_member')
            ->where('source', 'domain')
            ->count())->toBe(1);
});

it('treats wildcard roles as containing future catalogue permissions', function (): void {
    $school = School::factory()->create();
    $admin = securityRbacUser($school, 'school_admin');

    /** @var array<string, mixed> $permissions */
    $permissions = config('authorization.permissions', []);
    $permissions['future.permission'] = ['label' => 'Future permission'];
    config()->set('authorization.permissions', $permissions);

    expect(app(SchoolPermissions::class)->has($admin, $school, 'future.permission'))->toBeTrue();
});

it('rejects every client supplied school_id shape on role assignment', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $admin = securityRbacUser($school, 'school_admin');
    $target = securityRbacUser($school);

    $cases = [
        'null' => [null, 422],
        'empty string' => ['', 422],
        'empty array' => [[], 400],
        'non-numeric string' => ['abc', 400],
        'authorised identifier' => [$school->id, 422],
        'foreign identifier' => [$other->id, 403],
    ];

    foreach ($cases as [$schoolId, $status]) {
        $this->actingAs($admin)
            ->postJson('/api/role-assignments', [
                'user_id' => $target->id,
                'role' => 'school_member',
                'school_id' => $schoolId,
            ])
            ->assertStatus($status);
    }

    expect(RoleAssignment::query()->forUser($target)->count())->toBe(0);
});
