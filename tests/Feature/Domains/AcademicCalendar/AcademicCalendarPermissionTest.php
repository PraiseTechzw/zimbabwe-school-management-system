<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function calendarPermissionUser(School $school, ?string $role): User
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
function calendarPermissionYearData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Permission year',
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
        'source_reference' => 'calendar-register/2031',
        'source_published_at' => '2030-11-01',
    ], $overrides);
}

/**
 * @return array<string, mixed>
 */
function calendarPermissionPeriodData(AcademicYear $year, array $overrides = []): array
{
    return array_merge([
        'academic_year_id' => (int) $year->id,
        'name' => 'Permission period',
        'type' => 'term',
        'sequence' => 1,
        'start_date' => '2031-01-10',
        'end_date' => '2031-03-31',
        'status' => 'published',
        'counting_basis' => 'school_days',
        'source' => 'School calendar register',
    ], $overrides);
}

it('rejects anonymous academic calendar writes', function (): void {
    $school = School::factory()->create();
    $year = AcademicYear::factory()->for($school)->create(calendarPermissionYearData());
    $period = AcademicPeriod::factory()->inYear($year)->create(calendarPermissionPeriodData($year));

    $this->postJson('/api/academic-years', calendarPermissionYearData())->assertUnauthorized();
    $this->postJson('/api/academic-years/'.$year->id.'/periods', calendarPermissionPeriodData($year))->assertUnauthorized();
    $this->putJson('/api/academic-years/'.$year->id, calendarPermissionYearData(['name' => 'Anonymous write']))->assertUnauthorized();
    $this->putJson('/api/academic-periods/'.$period->id, calendarPermissionPeriodData($year, ['name' => 'Anonymous write']))->assertUnauthorized();

    expect(AcademicYear::query()->where('name', 'Anonymous write')->count())->toBe(0)
        ->and(AcademicPeriod::query()->where('name', 'Anonymous write')->count())->toBe(0);
});

it('denies calendar writes to authenticated members without the manage permission', function (?string $role): void {
    $school = School::factory()->create();
    $user = calendarPermissionUser($school, $role);
    $year = AcademicYear::factory()->for($school)->create(calendarPermissionYearData());
    $period = AcademicPeriod::factory()->inYear($year)->create(calendarPermissionPeriodData($year));
    $before = AuditEvent::query()->count();

    $this->actingAs($user)
        ->postJson('/api/academic-years', calendarPermissionYearData(['name' => 'Forbidden year']))
        ->assertForbidden();
    $this->actingAs($user)
        ->postJson('/api/academic-years/'.$year->id.'/periods', calendarPermissionPeriodData($year, ['name' => 'Forbidden period']))
        ->assertForbidden();
    $this->actingAs($user)
        ->putJson('/api/academic-years/'.$year->id, calendarPermissionYearData(['name' => 'Forbidden update']))
        ->assertForbidden();
    $this->actingAs($user)
        ->putJson('/api/academic-periods/'.$period->id, calendarPermissionPeriodData($year, ['name' => 'Forbidden update']))
        ->assertForbidden();

    expect(AcademicYear::query()->where('name', 'Forbidden year')->count())->toBe(0)
        ->and(AcademicYear::query()->where('name', 'Forbidden update')->count())->toBe(0)
        ->and(AcademicPeriod::query()->where('name', 'Forbidden period')->count())->toBe(0)
        ->and(AcademicPeriod::query()->where('name', 'Forbidden update')->count())->toBe(0)
        ->and(AuditEvent::query()->count())->toBe($before);
})->with([null, 'role_viewer', 'school_member']);

it('allows configured editors and administrators to perform calendar writes', function (string $role): void {
    $school = School::factory()->create();
    $user = calendarPermissionUser($school, $role);

    $created = $this->actingAs($user)
        ->postJson('/api/academic-years', calendarPermissionYearData(['name' => 'Authorised year']))
        ->assertCreated()
        ->json('academic_year');

    expect($created)->not->toBeNull();

    $period = $this->actingAs($user)
        ->postJson('/api/academic-years/'.$created['id'].'/periods', calendarPermissionPeriodData(
            AcademicYear::query()->findOrFail($created['id']),
            ['name' => 'Authorised period'],
        ))
        ->assertCreated()
        ->json('period');

    expect($period)->not->toBeNull();

    $this->actingAs($user)
        ->putJson('/api/academic-years/'.$created['id'], calendarPermissionYearData(['name' => 'Updated year']))
        ->assertOk()
        ->assertJsonPath('academic_year.name', 'Updated year');
    $this->actingAs($user)
        ->putJson('/api/academic-periods/'.$period['id'], calendarPermissionPeriodData(
            AcademicYear::query()->findOrFail($created['id']),
            ['name' => 'Updated period'],
        ))
        ->assertOk()
        ->assertJsonPath('period.name', 'Updated period');
})->with(['school_editor', 'school_admin']);

it('enforces the calendar view permission on read endpoints', function (): void {
    $school = School::factory()->create();
    $viewer = calendarPermissionUser($school, 'role_viewer');
    $member = calendarPermissionUser($school, 'school_member');
    AcademicYear::factory()->for($school)->create(calendarPermissionYearData());

    $this->actingAs($viewer)->getJson('/api/academic-years')->assertForbidden();
    $this->actingAs($viewer)->getJson('/api/academic-years/current')->assertForbidden();
    $this->actingAs($viewer)->getJson('/app/academic-calendar')->assertForbidden();

    $this->actingAs($member)->getJson('/api/academic-years')->assertOk();
    $this->actingAs($member)->getJson('/app/academic-calendar')->assertOk();
});
