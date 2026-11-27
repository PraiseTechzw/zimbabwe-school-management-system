<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Http\Middleware\EnsurePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

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

it('applies the expected permission to every registered academic structure route', function (): void {
    $expected = [
        'GET api/academic-structure/levels' => 'academic_structure.view',
        'POST api/academic-structure/levels' => 'academic_structure.manage',
        'PUT api/academic-structure/levels/{educationalLevel}' => 'academic_structure.manage',
        'PATCH api/academic-structure/levels/{educationalLevel}' => 'academic_structure.manage',
        'GET api/academic-structure/grade-levels' => 'academic_structure.view',
        'GET api/academic-structure/levels/{educationalLevel}/grade-levels' => 'academic_structure.view',
        'POST api/academic-structure/levels/{educationalLevel}/grade-levels' => 'academic_structure.manage',
        'PUT api/academic-structure/grade-levels/{gradeLevel}' => 'academic_structure.manage',
        'PATCH api/academic-structure/grade-levels/{gradeLevel}' => 'academic_structure.manage',
        'GET api/academic-structure/grade-levels/{gradeLevel}/classes' => 'academic_structure.view',
        'POST api/academic-structure/grade-levels/{gradeLevel}/classes' => 'academic_structure.manage',
        'PUT api/academic-structure/classes/{classDefinition}' => 'academic_structure.manage',
        'PATCH api/academic-structure/classes/{classDefinition}' => 'academic_structure.manage',
        'GET api/academic-structure/classes/{classDefinition}/streams' => 'academic_structure.view',
        'POST api/academic-structure/classes/{classDefinition}/streams' => 'academic_structure.manage',
        'PUT api/academic-structure/streams/{stream}' => 'academic_structure.manage',
        'PATCH api/academic-structure/streams/{stream}' => 'academic_structure.manage',
        'GET api/academic-structure/class-offerings' => 'academic_structure.view',
        'POST api/academic-structure/class-offerings' => 'academic_structure.manage',
        'PUT api/academic-structure/class-offerings/{classOffering}' => 'academic_structure.manage',
        'PATCH api/academic-structure/class-offerings/{classOffering}' => 'academic_structure.manage',
    ];

    $actual = [];

    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();

        if (! str_starts_with($uri, 'api/academic-structure')) {
            continue;
        }

        $middleware = collect($route->gatherMiddleware())->first(
            fn (mixed $value): bool => is_string($value)
                && str_starts_with($value, EnsurePermission::class.':'),
        );

        expect($middleware)->not->toBeNull();

        foreach ($route->methods() as $method) {
            if ($method === 'HEAD' || $method === 'OPTIONS') {
                continue;
            }

            $actual[$method.' '.$uri] = explode(':', (string) $middleware, 2)[1];
        }
    }

    ksort($expected);
    ksort($actual);

    expect($actual)->toBe($expected);
});

/**
 * @return array<string, mixed>
 */
function structurePermissionFixture(School $school): array
{
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $year = AcademicYear::factory()->for($school)->create();
    $offering = ClassOffering::factory()->forClassAndYear($class, $year)->create();

    return compact('level', 'grade', 'class', 'year', 'offering');
}

it('applies academic structure permissions to nested and offering write routes', function (): void {
    $school = School::factory()->create();
    $user = structurePermissionUser($school, 'school_member');
    $fixture = structurePermissionFixture($school);

    $cases = [
        ['post', '/api/academic-structure/levels/'.$fixture['level']->id.'/grade-levels', [
            'name' => 'Denied grade', 'code' => 'DG', 'display_order' => 2, 'is_active' => true,
        ]],
        ['post', '/api/academic-structure/grade-levels/'.$fixture['grade']->id.'/classes', [
            'name' => 'Denied class', 'code' => 'DC', 'display_order' => 2, 'is_active' => true,
        ]],
        ['post', '/api/academic-structure/classes/'.$fixture['class']->id.'/streams', [
            'name' => 'Denied stream', 'code' => 'DS', 'display_order' => 2, 'is_active' => true,
        ]],
        ['post', '/api/academic-structure/class-offerings', [
            'class_definition_id' => $fixture['class']->id,
            'academic_year_id' => $fixture['year']->id,
            'name' => 'Denied offering',
        ]],
        ['put', '/api/academic-structure/class-offerings/'.$fixture['offering']->id, [
            'name' => 'Tampered offering',
        ]],
    ];

    foreach ($cases as [$method, $endpoint, $payload]) {
        $this->actingAs($user)
            ->json(strtoupper($method), $endpoint, $payload)
            ->assertForbidden();
    }

    expect(GradeLevel::query()->forSchool($school)->count())->toBe(1)
        ->and(ClassDefinition::query()->forSchool($school)->count())->toBe(1)
        ->and(Stream::query()->forSchool($school)->count())->toBe(0)
        ->and(ClassOffering::query()->forSchool($school)->count())->toBe(1);
});

it('lets school editors use nested and offering write routes', function (): void {
    $school = School::factory()->create();
    $user = structurePermissionUser($school, 'school_editor');
    $fixture = structurePermissionFixture($school);
    $offeringClass = ClassDefinition::factory()->inGrade($fixture['grade'])->create();

    $this->actingAs($user)
        ->postJson('/api/academic-structure/levels/'.$fixture['level']->id.'/grade-levels', [
            'name' => 'Allowed grade', 'code' => 'AG', 'display_order' => 2, 'is_active' => true,
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->postJson('/api/academic-structure/grade-levels/'.$fixture['grade']->id.'/classes', [
            'name' => 'Allowed class', 'code' => 'AC', 'display_order' => 2, 'is_active' => true,
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->postJson('/api/academic-structure/classes/'.$fixture['class']->id.'/streams', [
            'name' => 'Allowed stream', 'code' => 'AS', 'display_order' => 2, 'is_active' => true,
        ])
        ->assertCreated();

    $offering = $this->actingAs($user)
        ->postJson('/api/academic-structure/class-offerings', [
            'class_definition_id' => $offeringClass->id,
            'academic_year_id' => $fixture['year']->id,
            'name' => 'Allowed offering',
        ])
        ->assertCreated()
        ->json('class_offering');

    $this->actingAs($user)
        ->patchJson('/api/academic-structure/class-offerings/'.$offering['id'], [
            'name' => 'Renamed allowed offering',
        ])
        ->assertOk()
        ->assertJsonPath('class_offering.name', 'Renamed allowed offering');
});
