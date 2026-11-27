<?php

declare(strict_types=1);

use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function structurePermissionUser(School $school, ?string $role): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    if ($role !== null) {
        RoleAssignment::query()->forceCreate([
            'school_id' => (int) $school->id,
            'user_id' => (int) $user->id,
            'role' => $role,
            'source' => 'domain',
            'created_at' => Carbon::now(),
        ]);
    }

    return $user;
}

/**
 * @return array<string, mixed>
 */
function structurePermissionLevelData(): array
{
    return [
        'name' => 'Permission level',
        'code' => 'PL',
        'display_order' => 1,
        'is_active' => true,
    ];
}

it('requires a permission bearing role to read academic structure responses', function (): void {
    $school = School::factory()->create();

    $this->actingAs(structurePermissionUser($school, null))
        ->getJson('/api/academic-structure/levels')
        ->assertForbidden();
});

it('does not grant structure permissions to the role viewer role', function (): void {
    $school = School::factory()->create();
    $user = structurePermissionUser($school, 'role_viewer');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson('/api/academic-structure/levels', structurePermissionLevelData())
        ->assertForbidden();
});

it('lets school members read structure configuration but denies writes', function (): void {
    $school = School::factory()->create();
    $user = structurePermissionUser($school, 'school_member');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertOk();

    $this->actingAs($user)
        ->postJson('/api/academic-structure/levels', structurePermissionLevelData())
        ->assertForbidden();

    expect(EducationalLevel::query()->forSchool($school)->count())->toBe(0);
});

it('lets school editors create and update structure configuration', function (): void {
    $school = School::factory()->create();
    $user = structurePermissionUser($school, 'school_editor');

    $created = $this->actingAs($user)
        ->postJson('/api/academic-structure/levels', structurePermissionLevelData())
        ->assertCreated()
        ->json('educational_level');

    expect($created)->not->toBeNull();

    $this->actingAs($user)
        ->patchJson('/api/academic-structure/levels/'.$created['id'], [
            'name' => 'Updated permission level',
        ])
        ->assertOk()
        ->assertJsonPath('educational_level.name', 'Updated permission level');
});

it('lets school administrators manage structure through the wildcard role', function (): void {
    $school = School::factory()->create();
    $user = structurePermissionUser($school, 'school_admin');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertOk();

    $created = $this->actingAs($user)
        ->postJson('/api/academic-structure/levels', structurePermissionLevelData())
        ->assertCreated()
        ->json('educational_level');

    expect($created)->not->toBeNull();
});

it('does not grant structure permissions from a role assigned in another school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = structurePermissionUser($school, null);

    RoleAssignment::query()->forceCreate([
        'school_id' => (int) $otherSchool->id,
        'user_id' => (int) $user->id,
        'role' => 'school_admin',
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertForbidden();

    expect(EducationalLevel::query()->forSchool($school)->count())->toBe(0);
});
