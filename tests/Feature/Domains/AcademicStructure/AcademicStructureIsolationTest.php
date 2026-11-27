<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
use App\Domains\AcademicStructure\Actions\CreateClassDefinition;
use App\Domains\AcademicStructure\Actions\CreateGradeLevel;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function structureHttpUser(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureHttpLevelData(array $overrides = []): array
{
    return array_merge([
        'name' => 'HTTP level',
        'code' => 'HL',
        'display_order' => 1,
        'is_active' => true,
    ], $overrides);
}

/**
 * @return array<string, mixed>
 */
function structureHttpGradeData(): array
{
    return [
        'name' => 'HTTP grade',
        'code' => 'HG',
        'display_order' => 1,
        'is_active' => true,
    ];
}

/**
 * @return array<string, mixed>
 */
function structureHttpClassData(): array
{
    return [
        'name' => 'HTTP class',
        'code' => 'HC',
        'display_order' => 1,
        'is_active' => true,
    ];
}

/**
 * @return array<string, mixed>
 */
function structureHttpStreamData(): array
{
    return [
        'name' => 'HTTP stream',
        'code' => 'HS',
        'display_order' => 1,
        'is_active' => true,
    ];
}

it('requires authentication and school context for academic structure endpoints', function (): void {
    $this->getJson('/api/academic-structure/levels')->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/academic-structure/levels')
        ->assertForbidden();
});

it('requires an explicit school selection for users in more than one school', function (): void {
    $user = User::factory()->create();
    $user->schools()->attach(School::factory()->create());
    $user->schools()->attach(School::factory()->create());

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertBadRequest();
});

it('creates and lists configurable levels, grades, classes and streams through the API', function (): void {
    $school = School::factory()->create();
    $user = structureHttpUser($school);

    $level = $this->actingAs($user)
        ->postJson('/api/academic-structure/levels', structureHttpLevelData(['name' => 'Foundation']))
        ->assertCreated()
        ->assertJsonPath('educational_level.name', 'Foundation')
        ->assertJsonPath('educational_level.school_id', $school->id)
        ->assertHeader('Vary', 'X-School-Id, school_id');

    expect($level->headers->get('Cache-Control'))->toContain('no-store');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertOk()
        ->assertJsonCount(1, 'educational_levels')
        ->assertJsonPath('educational_levels.0.name', 'Foundation')
        ->assertJsonPath('school_id', $school->id);

    $levelId = (int) $level->json('educational_level.id');

    $grade = $this->actingAs($user)
        ->postJson('/api/academic-structure/levels/'.$levelId.'/grade-levels', structureHttpGradeData())
        ->assertCreated()
        ->assertJsonPath('grade_level.educational_level_id', $levelId);

    $gradeId = (int) $grade->json('grade_level.id');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels/'.$levelId.'/grade-levels')
        ->assertOk()
        ->assertJsonCount(1, 'grade_levels')
        ->assertJsonPath('grade_levels.0.id', $gradeId);

    $class = $this->actingAs($user)
        ->postJson('/api/academic-structure/grade-levels/'.$gradeId.'/classes', structureHttpClassData())
        ->assertCreated()
        ->assertJsonPath('class.grade_level_id', $gradeId);

    $classId = (int) $class->json('class.id');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/grade-levels/'.$gradeId.'/classes')
        ->assertOk()
        ->assertJsonCount(1, 'classes')
        ->assertJsonPath('classes.0.id', $classId);

    $this->actingAs($user)
        ->postJson('/api/academic-structure/classes/'.$classId.'/streams', structureHttpStreamData())
        ->assertCreated()
        ->assertJsonPath('stream.class_definition_id', $classId);

    $this->actingAs($user)
        ->getJson('/api/academic-structure/classes/'.$classId.'/streams')
        ->assertOk()
        ->assertJsonCount(1, 'streams')
        ->assertJsonPath('streams.0.name', 'HTTP stream');
});

it('creates and filters academic year class offerings through the API', function (): void {
    $school = School::factory()->create();
    $user = structureHttpUser($school);
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();

    $year = app(CreateAcademicYear::class)->execute($school, [
        'name' => 'Structure year',
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
    ]);

    $offering = $this->actingAs($user)
        ->postJson('/api/academic-structure/class-offerings', [
            'class_definition_id' => $class->id,
            'academic_year_id' => $year->id,
            'name' => 'Named offering',
        ])
        ->assertCreated()
        ->assertJsonPath('class_offering.academic_year_id', $year->id)
        ->assertHeader('Vary', 'X-School-Id, school_id');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/class-offerings?academic_year_id='.$year->id)
        ->assertOk()
        ->assertJsonCount(1, 'class_offerings')
        ->assertJsonPath('class_offerings.0.id', $offering->json('class_offering.id'));

    $this->actingAs($user)
        ->putJson('/api/academic-structure/class-offerings/'.$offering->json('class_offering.id'), [
            'name' => 'Renamed offering',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('class_offering.name', 'Renamed offering')
        ->assertJsonPath('class_offering.is_active', false);
});

it('rejects client supplied school identifiers on every write endpoint', function (): void {
    $school = School::factory()->create();
    $user = structureHttpUser($school);
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();

    $endpoints = [
        ['post', '/api/academic-structure/levels', structureHttpLevelData()],
        ['put', '/api/academic-structure/levels/'.$level->id, structureHttpLevelData()],
        ['post', '/api/academic-structure/levels/'.$level->id.'/grade-levels', structureHttpGradeData()],
        ['put', '/api/academic-structure/grade-levels/'.$grade->id, structureHttpGradeData()],
        ['post', '/api/academic-structure/grade-levels/'.$grade->id.'/classes', structureHttpClassData()],
        ['put', '/api/academic-structure/classes/'.$class->id, structureHttpClassData()],
        ['post', '/api/academic-structure/classes/'.$class->id.'/streams', structureHttpStreamData()],
    ];

    foreach ($endpoints as [$method, $endpoint, $payload]) {
        $payload['school_id'] = $school->id;

        $this->actingAs($user)
            ->json(strtoupper($method), $endpoint, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('school_id');
    }

    $this->actingAs($user)
        ->getJson('/api/academic-structure/class-offerings?school_id='.$school->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');
});

it('uses route parents instead of client supplied foreign identifiers', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = structureHttpUser($school);
    $level = EducationalLevel::factory()->for($school)->create();
    $foreignLevel = EducationalLevel::factory()->for($other)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $foreignGrade = GradeLevel::factory()->inLevel($foreignLevel)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $foreignClass = ClassDefinition::factory()->inGrade($foreignGrade)->create();

    $this->actingAs($user)
        ->postJson('/api/academic-structure/levels/'.$level->id.'/grade-levels', structureHttpGradeData())
        ->assertCreated()
        ->assertJsonPath('grade_level.educational_level_id', $level->id);

    $this->actingAs($user)
        ->postJson('/api/academic-structure/grade-levels/'.$grade->id.'/classes', structureHttpClassData())
        ->assertCreated()
        ->assertJsonPath('class.grade_level_id', $grade->id);

    $this->actingAs($user)
        ->postJson('/api/academic-structure/classes/'.$class->id.'/streams', structureHttpStreamData())
        ->assertCreated()
        ->assertJsonPath('stream.class_definition_id', $class->id);

    $this->assertDatabaseCount('grade_levels', 3)
        ->assertDatabaseCount('class_definitions', 3)
        ->assertDatabaseCount('streams', 1);
});

it('does not expose, reference or update another school structure', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = structureHttpUser($school);

    $ownedLevel = EducationalLevel::factory()->for($school)->create(['name' => 'Owned level']);
    $foreignLevel = EducationalLevel::factory()->for($other)->create(['name' => 'Foreign level']);
    $foreignGrade = GradeLevel::factory()->inLevel($foreignLevel)->create(['name' => 'Foreign grade']);
    $foreignClass = ClassDefinition::factory()->inGrade($foreignGrade)->create(['name' => 'Foreign class']);
    $foreignStream = Stream::factory()->inClass($foreignClass)->create(['name' => 'Foreign stream']);

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels')
        ->assertOk()
        ->assertJsonCount(1, 'educational_levels')
        ->assertJsonPath('educational_levels.0.id', $ownedLevel->id);

    $this->actingAs($user)
        ->getJson('/api/academic-structure/grade-levels')
        ->assertOk()
        ->assertJsonCount(0, 'grade_levels');

    $this->actingAs($user)
        ->getJson('/api/academic-structure/levels/'.$foreignLevel->id.'/grade-levels')
        ->assertNotFound();

    $this->actingAs($user)
        ->getJson('/api/academic-structure/grade-levels/'.$foreignGrade->id.'/classes')
        ->assertNotFound();

    $this->actingAs($user)
        ->getJson('/api/academic-structure/classes/'.$foreignClass->id.'/streams')
        ->assertNotFound();

    $this->actingAs($user)
        ->putJson('/api/academic-structure/levels/'.$foreignLevel->id, structureHttpLevelData(['name' => 'Tampered level']))
        ->assertNotFound();

    $this->actingAs($user)
        ->putJson('/api/academic-structure/grade-levels/'.$foreignGrade->id, structureHttpGradeData())
        ->assertNotFound();

    $this->actingAs($user)
        ->putJson('/api/academic-structure/classes/'.$foreignClass->id, structureHttpClassData())
        ->assertNotFound();

    $this->actingAs($user)
        ->putJson('/api/academic-structure/streams/'.$foreignStream->id, structureHttpStreamData())
        ->assertNotFound();

    $this->actingAs($user)
        ->getJson('/api/academic-structure/class-offerings?academic_year_id=999999')
        ->assertNotFound();

    $this->assertDatabaseHas('educational_levels', ['id' => $foreignLevel->id, 'name' => 'Foreign level'])
        ->assertDatabaseHas('grade_levels', ['id' => $foreignGrade->id, 'name' => 'Foreign grade'])
        ->assertDatabaseHas('class_definitions', ['id' => $foreignClass->id, 'name' => 'Foreign class'])
        ->assertDatabaseHas('streams', ['id' => $foreignStream->id, 'name' => 'Foreign stream']);
});

it('rejects an unauthorised school header without changing the resolved context', function (): void {
    $owned = School::factory()->create();
    $other = School::factory()->create();
    $user = structureHttpUser($owned);
    EducationalLevel::factory()->for($owned)->create(['name' => 'Owned']);
    EducationalLevel::factory()->for($other)->create(['name' => 'Other']);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $other->id)
        ->getJson('/api/academic-structure/levels')
        ->assertForbidden();
});

it('fails closed for unscoped structure queries and cross-school domain writes', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $foreignLevel = EducationalLevel::factory()->for($other)->create();
    $foreignGrade = GradeLevel::factory()->inLevel($foreignLevel)->create();
    $foreignClass = ClassDefinition::factory()->inGrade($foreignGrade)->create();

    expect(fn (): int => EducationalLevel::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class)
        ->and(fn (): int => GradeLevel::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class)
        ->and(fn (): int => ClassDefinition::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class)
        ->and(fn (): int => Stream::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class)
        ->and(fn (): int => ClassOffering::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(CreateGradeLevel::class)->execute($school, [
        'educational_level_id' => $foreignLevel->id,
        'name' => 'Rejected',
        'display_order' => 1,
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(CreateClassDefinition::class)->execute($school, [
        'grade_level_id' => $foreignGrade->id,
        'name' => 'Rejected',
        'display_order' => 1,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('class_definitions', ['id' => $foreignClass->id]);
});

it('does not reveal foreign parent identifiers in validation messages', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = structureHttpUser($school);
    $foreignLevel = EducationalLevel::factory()->for($other)->create(['name' => 'Secret school level']);

    $response = $this->actingAs($user)
        ->postJson('/api/academic-structure/levels/'.$foreignLevel->id.'/grade-levels', structureHttpGradeData())
        ->assertNotFound();

    expect((string) json_encode($response->json()))->not->toContain('Secret school level');

    $this->assertDatabaseCount('grade_levels', 0);
});
