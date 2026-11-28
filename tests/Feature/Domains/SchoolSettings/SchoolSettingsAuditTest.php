<?php

declare(strict_types=1);

use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Actions\UpdateSchoolSettings;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function schoolSettingsAuditUser(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    return $user;
}

it('attributes the first settings save to the authenticated actor and server resolved school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = schoolSettingsAuditUser($school);

    $this->actingAs($user);

    $settings = app(UpdateSchoolSettings::class)->execute($school, [
        'locale' => 'en',
        'timezone' => 'Africa/Harare',
        'source' => 'Initial register entry',
        'effective_from' => '2031-01-01',
    ]);

    $event = AuditEvent::query()
        ->forSchool($school)
        ->where('action', 'school_settings.created')
        ->firstOrFail();

    expect((int) $event->school_id)->toBe((int) $school->id)
        ->and($event->actor_type)->toBe('user')
        ->and($event->actor_id)->toBe((string) $user->id)
        ->and($event->category)->toBe('school_configuration')
        ->and($event->source)->toBe('domain')
        ->and($event->resource_type)->toBe('school_setting')
        ->and($event->resource_id)->toBe((string) $settings->id)
        ->and($event->changes['before'])->toBeNull()
        ->and($event->changes['after'])->toBe([
            'version' => 1,
            'values' => [
                'locale' => 'en',
                'timezone' => 'Africa/Harare',
            ],
            'source' => 'Initial register entry',
            'effective_from' => '2031-01-01',
        ])
        ->and($event->changes['after'])->not->toHaveKey('school_id')
        ->and(AuditEvent::query()->forSchool($otherSchool)->count())->toBe(0);
});

it('records settings updates with previous and current scalar snapshots', function (): void {
    $school = School::factory()->create();
    $user = schoolSettingsAuditUser($school);

    $this->actingAs($user);

    app(UpdateSchoolSettings::class)->execute($school, [
        'timezone' => 'Africa/Harare',
        'source' => 'Initial register entry',
        'effective_from' => '2031-01-01',
    ]);

    $updated = app(UpdateSchoolSettings::class)->execute($school, [
        'timezone' => 'UTC',
        'source' => 'Principal approval',
        'effective_from' => '2031-02-01',
    ]);

    $events = AuditEvent::query()
        ->forSchool($school)
        ->where('resource_type', 'school_setting')
        ->orderBy('id')
        ->get();

    expect($events->pluck('action')->all())->toBe([
        'school_settings.created',
        'school_settings.updated',
    ]);

    $update = $events->last();

    expect((int) $update->school_id)->toBe((int) $school->id)
        ->and($update->actor_type)->toBe('user')
        ->and($update->actor_id)->toBe((string) $user->id)
        ->and($update->resource_id)->toBe((string) $updated->id)
        ->and($update->changes['before'])->toBe([
            'version' => 1,
            'values' => [
                'locale' => 'en',
                'timezone' => 'Africa/Harare',
            ],
            'source' => 'Initial register entry',
            'effective_from' => '2031-01-01',
        ])
        ->and($update->changes['after'])->toBe([
            'version' => 2,
            'values' => [
                'locale' => 'en',
                'timezone' => 'UTC',
            ],
            'source' => 'Principal approval',
            'effective_from' => '2031-02-01',
        ]);

    expect(SchoolSetting::query()->forSchool($school)->firstOrFail()->revisions()->count())->toBe(1);
});

it('does not append a settings audit event when domain validation rejects the write', function (): void {
    $school = School::factory()->create();

    try {
        app(UpdateSchoolSettings::class)->execute($school, [
            'timezone' => 'Not/AZone',
            'source' => 'Rejected entry',
        ]);
        $this->fail('An invalid timezone should fail validation.');
    } catch (ValidationException) {
        // Expected: rejected writes must not append audit events.
    }

    expect(SchoolSetting::query()->forSchool($school)->count())->toBe(0)
        ->and(AuditEvent::query()->forSchool($school)->count())->toBe(0);
});

it('rolls back the settings write, revision and audit event when a surrounding transaction fails', function (): void {
    $school = School::factory()->create();
    $user = schoolSettingsAuditUser($school);

    $this->actingAs($user);

    expect(fn () => DB::transaction(function () use ($school): void {
        app(UpdateSchoolSettings::class)->execute($school, [
            'source' => 'Will be rolled back',
            'effective_from' => '2031-03-01',
        ]);

        throw new RuntimeException('forced rollback');
    }))->toThrow(RuntimeException::class);

    expect(SchoolSetting::query()->forSchool($school)->count())->toBe(0)
        ->and(AuditEvent::query()->forSchool($school)->count())->toBe(0);
});
