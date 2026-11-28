<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function learnerIsolationUser(School $school, string $role = 'school_admin'): User
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

/**
 * @return array<string, mixed>
 */
function learnerIsolationPayload(int $schoolId): array
{
    return [
        'school_id' => $schoolId,
        'full_name' => 'Rejected Learner',
        'status' => 'registered',
    ];
}

function learnerIsolationOffering(School $school): ClassOffering
{
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $year = app(CreateAcademicYear::class)->execute($school, [
        'name' => '2032',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
    ]);

    /** @var ClassOffering $offering */
    $offering = ClassOffering::factory()
        ->forClassAndYear($class, $year)
        ->create();

    return $offering;
}

it('requires authentication and an authorised school context for learner endpoints', function (): void {
    $this->getJson('/api/learners')->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/learners')
        ->assertForbidden();
});

it('requires an explicit school selection for users in more than one school', function (): void {
    $user = User::factory()->create();
    $user->schools()->attach(School::factory()->create());
    $user->schools()->attach(School::factory()->create());

    $this->actingAs($user)
        ->getJson('/api/learners')
        ->assertBadRequest();
});

it('rejects a client supplied school id on every write and on the list query', function (): void {
    $school = School::factory()->create();
    $user = learnerIsolationUser($school);
    $learner = Learner::factory()->for($school)->create();

    $this->actingAs($user)
        ->postJson('/api/learners', learnerIsolationPayload((int) $school->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['school_id', 'status']);

    $this->actingAs($user)
        ->patchJson('/api/learners/'.$learner->id, learnerIsolationPayload((int) $school->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['school_id', 'status']);

    $this->actingAs($user)
        ->getJson('/api/learners?school_id='.$school->id)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('school_id');

    expect(Learner::query()->forSchool($school)->count())->toBe(1)
        ->and(Learner::query()->forSchool($school)->where('full_name', 'Rejected Learner')->count())->toBe(0);
});

it('does not accept an unauthorised school selection header', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = learnerIsolationUser($school);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $other->id)
        ->getJson('/api/learners')
        ->assertForbidden();
});

it('returns a generic 404 for missing and foreign learner route targets', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = learnerIsolationUser($school);
    $foreign = Learner::factory()->for($other)->create();

    $missing = $this->actingAs($user)->getJson('/api/learners/999999')->assertNotFound();
    $hidden = $this->actingAs($user)->getJson('/api/learners/'.$foreign->id)->assertNotFound();

    expect($missing->json('message'))->toBe($hidden->json('message'));

    $this->actingAs($user)
        ->patchJson('/api/learners/'.$foreign->id, ['full_name' => 'Should not change'])
        ->assertNotFound();

    expect($foreign->fresh()->full_name)->not->toBe('Should not change');
});

it('rejects cross-school placement references without revealing another tenant', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = learnerIsolationUser($school);
    $foreignOffering = learnerIsolationOffering($other);

    $response = $this->actingAs($user)
        ->postJson('/api/learners', [
            'full_name' => 'Cross School Placement',
            'class_offering_id' => (int) $foreignOffering->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('class_offering_id');

    expect($response->json('errors.class_offering_id.0'))->toBe('The selected class offering is not available.')
        ->and(str_contains((string) json_encode($response->json()), (string) $other->name))->toBeFalse()
        ->and(Learner::query()->forSchool($school)->count())->toBe(0);
});

it('fails closed when a domain update is handed a learner from another school', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $foreign = Learner::factory()->for($other)->create();

    expect(fn () => app(UpdateLearner::class)->execute(
        $school,
        $foreign,
        ['full_name' => 'Cross School Edit'],
    ))->toThrow(AuthorizationException::class);

    expect($foreign->fresh()->full_name)->not->toBe('Cross School Edit');
});

it('only lists learners from the authorised school with tenant-varying response headers', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = learnerIsolationUser($school);

    app(RegisterLearner::class)->execute($school, ['full_name' => 'Owned Learner']);
    app(RegisterLearner::class)->execute($other, ['full_name' => 'Foreign Learner']);

    $response = $this->actingAs($user)
        ->getJson('/api/learners')
        ->assertOk()
        ->assertJsonPath('school_id', $school->id)
        ->assertJsonPath('learners.total', 1)
        ->assertJsonPath('learners.data.0.full_name', 'Owned Learner')
        ->assertHeader('Vary', 'X-School-Id, school_id');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(str_contains((string) json_encode($response->json()), 'Foreign Learner'))->toBeFalse();
});
