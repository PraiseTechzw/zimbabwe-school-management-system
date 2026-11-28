<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\LearnerManagement\Actions\RegisterLearner;
use App\Domains\LearnerManagement\Actions\UpdateLearner;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function learnerAuditOffering(School $school, bool $active = true): ClassOffering
{
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $year = AcademicYear::factory()->for($school)->create();

    /** @var ClassOffering $offering */
    $offering = ClassOffering::factory()
        ->forClassAndYear($class, $year)
        ->create(['is_active' => $active]);

    return $offering;
}

function learnerAuditApiUser(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    RoleAssignment::query()->forceCreate([
        'school_id' => (int) $school->id,
        'user_id' => (int) $user->id,
        'role' => 'school_admin',
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    return $user;
}

it('records privacy-minimised audit events for learner writes', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $offering = learnerAuditOffering($school);

    $learner = app(RegisterLearner::class)->execute($school, [
        'full_name' => 'Private Learner',
        'date_of_birth' => '2011-03-04',
        'class_offering_id' => (int) $offering->id,
    ]);

    app(UpdateLearner::class)->execute($school, $learner, [
        'full_name' => 'Corrected Private Learner',
        'date_of_birth' => null,
        'class_offering_id' => null,
    ]);

    $events = AuditEvent::query()
        ->forSchool($school)
        ->where('category', 'learner_record')
        ->where('resource_type', 'learner')
        ->orderBy('id')
        ->get();

    expect($events->pluck('action')->all())->toBe([
        'learner.registered',
        'learner.updated',
    ]);

    $created = $events->first();
    $updated = $events->last();

    expect((int) $created->school_id)->toBe((int) $school->id)
        ->and($created->source)->toBe('domain')
        ->and($created->resource_id)->toBe((string) $learner->id)
        ->and($created->changes['after']['status'])->toBe('registered')
        ->and($created->changes['after']['full_name_present'])->toBeTrue()
        ->and($created->changes['after']['date_of_birth_present'])->toBeTrue()
        ->and($created->changes['after']['class_offering_id'])->toBe((int) $offering->id)
        ->and($updated->changes['before']['full_name_present'])->toBeTrue()
        ->and($updated->changes['before']['date_of_birth_present'])->toBeTrue()
        ->and($updated->changes['after']['date_of_birth_present'])->toBeFalse()
        ->and($updated->changes['after']['class_offering_id'])->toBeNull();

    foreach ($events as $event) {
        $serialized = (string) json_encode([
            'metadata' => $event->metadata,
            'changes' => $event->changes,
        ]);

        expect($serialized)->not->toContain('Private Learner')
            ->not->toContain('2011-03-04')
            ->not->toContain($learner->learner_reference);
    }

    expect(AuditEvent::query()->forSchool($other)->where('resource_type', 'learner')->count())->toBe(0);
});

it('does not record an audit event when a learner write is rejected', function (): void {
    $school = School::factory()->create();
    $inactiveOffering = learnerAuditOffering($school, false);

    foreach ([
        ['full_name' => '   '],
        ['full_name' => 'Invalid Placement Learner', 'class_offering_id' => (int) $inactiveOffering->id],
    ] as $payload) {
        try {
            app(RegisterLearner::class)->execute($school, $payload);
            $this->fail('Invalid learner input should fail validation.');
        } catch (ValidationException) {
            // Expected: rejected writes must not append audit events.
        }
    }

    expect(Learner::query()->forSchool($school)->count())->toBe(0)
        ->and(AuditEvent::query()->forSchool($school)->where('category', 'learner_record')->count())->toBe(0);
});

it('does not audit reads or cross-school domain updates', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = learnerAuditApiUser($school);
    $foreign = Learner::factory()->for($other)->create();

    app(RegisterLearner::class)->execute($school, ['full_name' => 'Audited Learner']);

    $this->actingAs($user)->getJson('/api/learners')->assertOk();
    $this->actingAs($user)->getJson('/api/learners/'.$foreign->id)->assertNotFound();

    try {
        app(UpdateLearner::class)->execute($school, $foreign, ['full_name' => 'Cross School Edit']);
        $this->fail('A cross-school domain update should be rejected.');
    } catch (AuthorizationException) {
        // Expected: the actor's school cannot edit another school's record.
    }

    expect(AuditEvent::query()->forSchool($school)->where('category', 'learner_record')->count())->toBe(1)
        ->and(AuditEvent::query()->forSchool($other)->where('category', 'learner_record')->count())->toBe(0)
        ->and($foreign->fresh()->full_name)->not->toBe('Cross School Edit');
});
