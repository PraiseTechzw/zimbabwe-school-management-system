<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\LearnerManagement\Actions\RegisterLearner;
use App\Domains\LearnerManagement\Actions\UpdateLearner;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function learnerData(array $overrides = []): array
{
    return array_merge([
        'full_name' => '  Example Learner  ',
        'date_of_birth' => null,
        'class_offering_id' => null,
    ], $overrides);
}

function learnerOffering(School $school, bool $active = true): ClassOffering
{
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $year = AcademicYear::factory()->for($school)->create();

    return ClassOffering::factory()
        ->forClassAndYear($class, $year)
        ->create(['is_active' => $active]);
}

function learnerApiUser(School $school, string $role = 'school_admin'): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    RoleAssignment::query()->forceCreate([
        'school_id' => (int) $school->id,
        'user_id' => (int) $user->id,
        'role' => $role,
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    return $user;
}

it('registers a trimmed, school-owned learner with a server-generated internal reference', function (): void {
    $school = School::factory()->create();
    $offering = learnerOffering($school);

    $learner = app(RegisterLearner::class)->execute($school, learnerData([
        'date_of_birth' => '2012-05-04',
        'class_offering_id' => (int) $offering->id,
    ]));

    expect($learner->exists)->toBeTrue()
        ->and($learner->full_name)->toBe('Example Learner')
        ->and($learner->date_of_birth)->toBe('2012-05-04')
        ->and($learner->status)->toBe('registered')
        ->and((int) $learner->school_id)->toBe((int) $school->id)
        ->and((int) $learner->class_offering_id)->toBe((int) $offering->id)
        ->and($learner->learner_reference)->toStartWith('LRN-')
        ->and(strlen($learner->learner_reference))->toBeLessThanOrEqual(32);

    $this->assertDatabaseHas('learners', [
        'id' => $learner->id,
        'school_id' => $school->id,
        'full_name' => 'Example Learner',
        'date_of_birth' => '2012-05-04',
        'status' => 'registered',
        'class_offering_id' => $offering->id,
    ]);
});

it('rejects invalid learner input in the domain action', function (array $overrides): void {
    $school = School::factory()->create();

    expect(fn () => app(RegisterLearner::class)->execute($school, learnerData($overrides)))
        ->toThrow(ValidationException::class);

    expect(Learner::query()->forSchool($school)->count())->toBe(0);
})->with([
    'blank name' => [['full_name' => '   ']],
    'oversized name' => [['full_name' => str_repeat('a', 256)]],
    'future birth date' => [['date_of_birth' => now()->addDay()->toDateString()]],
    'invalid birth date format' => [['date_of_birth' => '04/05/2012']],
    'zero offering id' => [['class_offering_id' => 0]],
    'non integer offering id' => [['class_offering_id' => 'not-an-id']],
]);

it('updates only writable fields and clears nullable values', function (): void {
    $school = School::factory()->create();
    $firstOffering = learnerOffering($school);
    $secondOffering = learnerOffering($school);
    $learner = app(RegisterLearner::class)->execute($school, learnerData([
        'date_of_birth' => '2010-01-02',
        'class_offering_id' => (int) $firstOffering->id,
    ]));
    $reference = $learner->learner_reference;

    $updated = app(UpdateLearner::class)->execute($school, $learner, [
        'full_name' => '  Corrected Name  ',
        'date_of_birth' => null,
        'class_offering_id' => (int) $secondOffering->id,
    ]);

    expect($updated->is($learner))->toBeTrue()
        ->and($updated->full_name)->toBe('Corrected Name')
        ->and($updated->date_of_birth)->toBeNull()
        ->and((int) $updated->class_offering_id)->toBe((int) $secondOffering->id)
        ->and($updated->learner_reference)->toBe($reference)
        ->and($updated->status)->toBe('registered');

    $cleared = app(UpdateLearner::class)->execute($school, $updated, ['class_offering_id' => null]);

    expect($cleared->class_offering_id)->toBeNull()
        ->and($cleared->full_name)->toBe('Corrected Name');
});

it('refuses placements that are foreign or inactive and leaves no partial record', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $foreignOffering = learnerOffering($other);
    $inactiveOffering = learnerOffering($school, false);

    expect(fn () => app(RegisterLearner::class)->execute($school, learnerData([
        'class_offering_id' => (int) $foreignOffering->id,
    ])))->toThrow(ValidationException::class);

    expect(fn () => app(RegisterLearner::class)->execute($school, learnerData([
        'class_offering_id' => (int) $inactiveOffering->id,
    ])))->toThrow(ValidationException::class);

    expect(Learner::query()->forSchool($school)->count())->toBe(0);
});

it('creates, retrieves, searches and paginates learners through the API', function (): void {
    $school = School::factory()->create();
    $user = learnerApiUser($school);

    $alpha = $this->actingAs($user)
        ->postJson('/api/learners', learnerData(['full_name' => 'Alpha Learner']))
        ->assertCreated()
        ->assertJsonPath('learner.full_name', 'Alpha Learner')
        ->assertJsonPath('learner.school_id', $school->id)
        ->assertJsonPath('learner.status', 'registered')
        ->assertHeader('Vary', 'X-School-Id, school_id')
        ->json('learner');

    $this->actingAs($user)
        ->postJson('/api/learners', learnerData(['full_name' => 'Beta Learner']))
        ->assertCreated();

    $this->actingAs($user)
        ->getJson('/api/learners/'.$alpha['id'])
        ->assertOk()
        ->assertJsonPath('learner.learner_reference', $alpha['learner_reference']);

    $this->actingAs($user)
        ->getJson('/api/learners?search=alpha')
        ->assertOk()
        ->assertJsonPath('search', 'alpha')
        ->assertJsonPath('learners.total', 1)
        ->assertJsonPath('learners.data.0.full_name', 'Alpha Learner');

    $this->actingAs($user)
        ->getJson('/api/learners?search='.$alpha['learner_reference'])
        ->assertOk()
        ->assertJsonPath('learners.total', 1)
        ->assertJsonPath('learners.data.0.id', $alpha['id']);

    $firstPage = $this->actingAs($user)
        ->getJson('/api/learners?per_page=1&page=1')
        ->assertOk()
        ->assertJsonPath('learners.total', 2)
        ->assertJsonPath('learners.per_page', 1)
        ->assertJsonPath('learners.last_page', 2)
        ->json('learners.data.0.id');

    $secondPage = $this->actingAs($user)
        ->getJson('/api/learners?per_page=1&page=2')
        ->assertOk()
        ->json('learners.data.0.id');

    expect($secondPage)->not->toBe($firstPage);
});

it('escapes SQL wildcard characters in the learner search term', function (): void {
    $school = School::factory()->create();
    $user = learnerApiUser($school);

    $this->actingAs($user)
        ->postJson('/api/learners', learnerData(['full_name' => 'Alpha Learner']))
        ->assertCreated();

    $this->actingAs($user)
        ->getJson('/api/learners?search=%25')
        ->assertOk()
        ->assertJsonPath('learners.total', 0);
});

it('updates a learner through the API without changing server-owned fields', function (): void {
    $school = School::factory()->create();
    $user = learnerApiUser($school);
    $learner = app(RegisterLearner::class)->execute($school, learnerData(['full_name' => 'Original Name']));

    $this->actingAs($user)
        ->patchJson('/api/learners/'.$learner->id, ['full_name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('learner.full_name', 'Updated Name')
        ->assertJsonPath('learner.learner_reference', $learner->learner_reference)
        ->assertJsonPath('learner.status', 'registered')
        ->assertJsonPath('learner.school_id', $school->id);
});
