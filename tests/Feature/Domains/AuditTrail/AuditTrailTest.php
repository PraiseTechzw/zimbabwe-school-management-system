<?php

declare(strict_types=1);

use App\Domains\AuditTrail\Actions\RecordAuditEvent;
use App\Domains\AuditTrail\AuditActor;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\School\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

function auditTrailUser(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    return $user;
}

it('records a school scoped event with actor, classification, resource and timestamp', function (): void {
    $school = School::factory()->create();
    $user = auditTrailUser($school);

    $this->actingAs($user);

    $this->app->instance('request', Request::create('/', 'GET', [], [], [], [
        'HTTP_X_CORRELATION_ID' => 'req-8001',
    ]));

    $event = app(RecordAuditEvent::class)->execute(
        AuditScope::forSchool($school),
        new AuditEventData(
            action: 'school_configuration.updated',
            resourceType: 'school_setting',
            resourceId: 'setting-17',
            category: 'school_configuration',
            metadata: ['source' => 'school register'],
            changes: [
                'before' => ['status' => 'draft'],
                'after' => ['status' => 'published'],
            ],
        ),
    );

    expect($event->school_id)->toBe($school->id)
        ->and($event->actor_type)->toBe('user')
        ->and($event->actor_id)->toBe((string) $user->id)
        ->and($event->action)->toBe('school_configuration.updated')
        ->and($event->category)->toBe('school_configuration')
        ->and($event->resource_type)->toBe('school_setting')
        ->and($event->resource_id)->toBe('setting-17')
        ->and($event->correlation_id)->toBe('req-8001')
        ->and($event->source)->toBe('domain')
        ->and($event->metadata)->toBe(['source' => 'school register'])
        ->and($event->changes['after']['status'])->toBe('published')
        ->and($event->occurred_at)->not()->toBeNull()
        ->and($event->created_at)->not()->toBeNull();
});

it('records an explicit system context without deriving a school from client input', function (): void {
    $occurredAt = Carbon::parse('2032-05-06 07:08:09');

    $event = app(RecordAuditEvent::class)->execute(
        AuditScope::system(),
        new AuditEventData(
            action: 'system.maintenance',
            resourceType: 'system',
            resourceId: 'cache',
            actor: AuditActor::job('nightly-cleanup'),
            source: 'job',
            occurredAt: $occurredAt,
        ),
    );

    expect($event->school_id)->toBeNull()
        ->and($event->actor_type)->toBe('job')
        ->and($event->actor_id)->toBe('nightly-cleanup')
        ->and($event->occurred_at->toDateTimeString())->toBe('2032-05-06 07:08:09')
        ->and($event->metadata)->toBeNull()
        ->and($event->changes)->toBeNull();
});

it('redacts sensitive metadata recursively instead of persisting raw values', function (): void {
    $school = School::factory()->create();

    $event = app(RecordAuditEvent::class)->execute(
        AuditScope::forSchool($school),
        new AuditEventData(
            action: 'school_configuration.updated',
            resourceType: 'school_setting',
            resourceId: 'setting-19',
            metadata: [
                'password' => 'hunter2-secret',
                'api_token' => 'token-abc',
                'card_number' => '4111111111111111',
                'medical_diagnosis' => 'do-not-store',
                'nested' => [
                    'remember_token' => 'nested-secret',
                    'safe_key' => 'safe-value',
                ],
            ],
            changes: [
                'before' => ['status' => 'draft', 'safeguarding_notes' => 'private-note'],
                'after' => ['status' => 'published', 'request_body' => 'whole-body'],
            ],
            actor: AuditActor::system(),
        ),
    );

    expect($event->metadata['password'])->toBe('[REDACTED]')
        ->and($event->metadata['api_token'])->toBe('[REDACTED]')
        ->and($event->metadata['card_number'])->toBe('[REDACTED]')
        ->and($event->metadata['medical_diagnosis'])->toBe('[REDACTED]')
        ->and($event->metadata['nested']['remember_token'])->toBe('[REDACTED]')
        ->and($event->metadata['nested']['safe_key'])->toBe('safe-value')
        ->and($event->changes['before']['safeguarding_notes'])->toBe('[REDACTED]')
        ->and($event->changes['after']['request_body'])->toBe('[REDACTED]');

    $stored = json_encode($event->toArray(), JSON_THROW_ON_ERROR);

    expect($stored)->not->toContain('hunter2-secret')
        ->and($stored)->not->toContain('token-abc')
        ->and($stored)->not->toContain('4111111111111111')
        ->and($stored)->not->toContain('private-note')
        ->and($stored)->not->toContain('whole-body');
});

it('rejects malformed audit input before writing a record', function (): void {
    $school = School::factory()->create();

    expect(fn () => new AuditEventData(
        action: 'School Configuration Updated',
        resourceType: 'school_setting',
        resourceId: '1',
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => app(RecordAuditEvent::class)->execute(
        AuditScope::forSchool($school),
        new AuditEventData(
            action: 'school_configuration.updated',
            resourceType: 'school_setting',
            resourceId: '1',
            metadata: ['request' => new stdClass],
            actor: AuditActor::system(),
        ),
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => app(RecordAuditEvent::class)->execute(
        AuditScope::forSchool($school),
        new AuditEventData(
            action: 'school_configuration.updated',
            resourceType: 'school_setting',
            resourceId: '1',
            changes: ['before' => 'not-an-array'],
            actor: AuditActor::system(),
        ),
    ))->toThrow(InvalidArgumentException::class);

    expect(AuditEvent::query()->count())->toBe(0);
});

it('ignores unsafe correlation header values', function (): void {
    $school = School::factory()->create();

    $this->actingAs(auditTrailUser($school));

    $this->app->instance('request', Request::create('/', 'GET', [], [], [], [
        'HTTP_X_CORRELATION_ID' => 'not a safe correlation id',
    ]));

    $event = app(RecordAuditEvent::class)->execute(
        AuditScope::forSchool($school),
        new AuditEventData(
            action: 'school_configuration.updated',
            resourceType: 'school_setting',
            resourceId: '1',
        ),
    );

    expect($event->correlation_id)->toBeNull();
});

it('blocks updates and deletes through both model instances and the query builder', function (): void {
    $event = AuditEvent::factory()->create(['school_id' => null]);

    expect(fn () => $event->update(['action' => 'tampered.action']))->toThrow(MassAssignmentException::class)
        ->and(fn () => $event->forceFill(['action' => 'tampered.action'])->save())->toThrow(LogicException::class)
        ->and(fn () => $event->delete())->toThrow(LogicException::class)
        ->and(fn () => AuditEvent::query()->whereKey($event->id)->update(['action' => 'tampered.action']))->toThrow(LogicException::class)
        ->and(fn () => AuditEvent::query()->whereKey($event->id)->delete())->toThrow(LogicException::class);

    expect(AuditEvent::query()->whereKey($event->id)->exists())->toBeTrue()
        ->and(AuditEvent::query()->whereKey($event->id)->value('action'))->toBe('test.event');
});

it('does not expose an unguarded audit HTTP endpoint', function (): void {
    $this->getJson('/api/audit-events')->assertNotFound();
});
