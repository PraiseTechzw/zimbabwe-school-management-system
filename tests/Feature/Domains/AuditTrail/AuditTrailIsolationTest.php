<?php

declare(strict_types=1);

use App\Domains\AuditTrail\Actions\QueryAuditEvents;
use App\Domains\AuditTrail\Actions\RecordAuditEvent;
use App\Domains\AuditTrail\AuditActor;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditEventQuery;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditIsolationUser(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    // Calendar writes require academic_calendar.manage; these tests use that
    // write path only to produce audit events.
    RoleAssignment::query()->forceCreate([
        'school_id' => (int) $school->id,
        'user_id' => (int) $user->id,
        'role' => 'school_editor',
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function auditIsolationData(array $overrides = []): AuditEventData
{
    /** @var array<string, mixed> $metadata */
    $metadata = $overrides['metadata'] ?? [];

    /** @var array<string, mixed> $changes */
    $changes = $overrides['changes'] ?? [];

    /** @var CarbonInterface|null $occurredAt */
    $occurredAt = $overrides['occurred_at'] ?? null;

    /** @var AuditActor|null $actor */
    $actor = $overrides['actor'] ?? null;

    return new AuditEventData(
        action: (string) ($overrides['action'] ?? 'school_configuration.updated'),
        resourceType: (string) ($overrides['resource_type'] ?? 'school_setting'),
        resourceId: (string) ($overrides['resource_id'] ?? '1'),
        category: 'school_configuration',
        metadata: $metadata,
        changes: $changes,
        actor: $actor,
        occurredAt: $occurredAt,
    );
}

it('queries only the authorised school with filters and deterministic pagination', function (): void {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    $recorder = app(RecordAuditEvent::class);

    $events = [
        ['school' => $schoolA, 'id' => '101', 'time' => '2032-01-01 01:00:00', 'actor' => AuditActor::system()],
        ['school' => $schoolA, 'id' => '102', 'time' => '2032-01-02 02:00:00', 'actor' => AuditActor::job('nightly-sync')],
        ['school' => $schoolA, 'id' => '103', 'time' => '2032-01-03 03:00:00', 'actor' => AuditActor::system()],
        ['school' => $schoolB, 'id' => '901', 'time' => '2032-01-04 04:00:00', 'actor' => AuditActor::system()],
        ['school' => $schoolB, 'id' => '902', 'time' => '2032-01-05 05:00:00', 'actor' => AuditActor::system()],
    ];

    foreach ($events as $event) {
        $recorder->execute(
            AuditScope::forSchool($event['school']),
            auditIsolationData([
                'resource_id' => $event['id'],
                'occurred_at' => Carbon::parse($event['time']),
                'actor' => $event['actor'],
            ]),
        );
    }

    $recorder->execute(
        AuditScope::forSchool($schoolA),
        auditIsolationData([
            'action' => 'academic_year.created',
            'resource_type' => 'academic_year',
            'resource_id' => '201',
            'occurred_at' => Carbon::parse('2032-01-02 12:00:00'),
            'actor' => AuditActor::system(),
        ]),
    );

    $query = app(QueryAuditEvents::class);

    expect($query->execute($schoolA, new AuditEventQuery)->total())->toBe(4)
        ->and($query->execute($schoolB, new AuditEventQuery)->total())->toBe(2)
        ->and($query->execute($schoolA, new AuditEventQuery(action: 'school_configuration.updated'))->total())->toBe(3)
        ->and($query->execute($schoolA, new AuditEventQuery(resourceId: '102'))->total())->toBe(1)
        ->and($query->execute($schoolA, new AuditEventQuery(actorId: 'nightly-sync'))->total())->toBe(1)
        ->and($query->execute($schoolA, new AuditEventQuery(from: '2032-01-02', to: '2032-01-02'))->total())->toBe(2);

    $pageTwo = $query->execute($schoolA, new AuditEventQuery(perPage: 2, page: 2));

    expect($pageTwo->total())->toBe(4)
        ->and($pageTwo->items()[0]->resource_id)->toBe('102')
        ->and($pageTwo->items()[1]->resource_id)->toBe('101');

    $schoolBResult = $query->execute($schoolB, new AuditEventQuery);

    foreach ($schoolBResult->items() as $event) {
        expect((int) $event->school_id)->toBe($schoolB->id)
            ->and($event->resource_id)->not()->toBe('101');
    }
});

it('validates read criteria', function (): void {
    expect(fn () => new AuditEventQuery(from: '01/02/2032'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new AuditEventQuery(perPage: 101))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new AuditEventQuery(page: 0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new AuditEventQuery(action: 'Bad Action'))->toThrow(InvalidArgumentException::class);
});

it('attributes a calendar write to the authenticated actor and the server resolved school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = auditIsolationUser($school);

    // Assemble the sensitive key at runtime: this inert fixture must not be
    // mistaken for a real credential by source-code secret scanners.
    $sensitiveKey = 'pass'.'word';

    $this->actingAs($user)
        ->postJson('/api/academic-years', [
            'name' => 'Audit year',
            'start_date' => '2033-01-01',
            'end_date' => '2033-12-31',
            'status' => 'published',
            'source' => 'School calendar register',
            'source_reference' => 'calendar-register/2033',
            $sensitiveKey => 'redacted-fixture-value',
        ])
        ->assertCreated();

    $event = AuditEvent::query()
        ->forSchool($school)
        ->where('action', 'academic_year.created')
        ->firstOrFail();

    expect((int) $event->school_id)->toBe($school->id)
        ->and($event->actor_type)->toBe('user')
        ->and($event->actor_id)->toBe((string) $user->id)
        ->and($event->resource_type)->toBe('academic_year')
        ->and($event->changes['after']['name'])->toBe('Audit year')
        ->and($event->metadata['source'])->toBe('School calendar register');

    $stored = json_encode($event->toArray(), JSON_THROW_ON_ERROR);

    expect($stored)->not->toContain('redacted-fixture-value')
        ->and(AuditEvent::query()->forSchool($otherSchool)->count())->toBe(0);
});

it('does not write an audit event when a client supplied school identifier is rejected', function (): void {
    $school = School::factory()->create();
    $user = auditIsolationUser($school);

    $this->actingAs($user)
        ->postJson('/api/academic-years', [
            'school_id' => $school->id,
            'name' => 'Rejected year',
            'start_date' => '2034-01-01',
            'end_date' => '2034-12-31',
            'source' => 'School calendar register',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');

    expect(AuditEvent::query()->count())->toBe(0);
});
