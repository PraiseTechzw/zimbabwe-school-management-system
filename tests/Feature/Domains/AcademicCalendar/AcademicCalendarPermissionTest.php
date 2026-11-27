<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
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
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calendarPermissionYearData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Permission year',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calendarPermissionPeriodData(AcademicYear $year, array $overrides = []): array
{
    return array_merge([
        'academic_year_id' => (int) $year->id,
        'name' => 'Permission period',
        'type' => 'term',
        'sequence' => 1,
        'start_date' => '2032-01-10',
        'end_date' => '2032-03-31',
        'status' => 'published',
        'counting_basis' => 'school_days',
        'source' => 'School calendar register',
    ], $overrides);
}

it('denies calendar writes without the academic_calendar.manage permission', function (?string $role): void {
    $school = School::factory()->create();
    $user = calendarPermissionUser($school, $role);
    $year = AcademicYear::factory()->for($school)->create(calendarPermissionYearData(['name' => 'Existing year']));
    $period = AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Existing period',
        'start_date' => '2032-01-10',
        'end_date' => '2032-03-31',
    ]);

    $cases = [
        ['post', '/api/academic-years', calendarPermissionYearData(['name' => 'Denied year'])],
        ['put', '/api/academic-years/'.$year->id, calendarPermissionYearData(['name' => 'Tampered year'])],
        ['post', '/api/academic-years/'.$year->id.'/periods', calendarPermissionPeriodData($year, ['name' => 'Denied period'])],
        ['put', '/api/academic-periods/'.$period->id, calendarPermissionPeriodData($year, ['name' => 'Tampered period'])],
    ];

    foreach ($cases as [$method, $endpoint, $payload]) {
        $this->actingAs($user)
            ->json(strtoupper($method), $endpoint, $payload)
            ->assertForbidden();
    }

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'name' => 'Existing year',
        'school_id' => $school->id,
    ])->assertDatabaseHas('academic_periods', [
        'id' => $period->id,
        'name' => 'Existing period',
        'school_id' => $school->id,
    ])->assertDatabaseMissing('academic_years', ['name' => 'Denied year'])
        ->assertDatabaseMissing('academic_periods', ['name' => 'Denied period']);
})->with([
    'no role' => null,
    'school member' => 'school_member',
    'role viewer' => 'role_viewer',
]);

it('lets calendar editors and administrators manage calendars', function (string $role): void {
    $school = School::factory()->create();
    $user = calendarPermissionUser($school, $role);

    $created = $this->actingAs($user)
        ->postJson('/api/academic-years', calendarPermissionYearData(['name' => 'Managed year']))
        ->assertCreated()
        ->json('academic_year');

    $this->actingAs($user)
        ->patchJson('/api/academic-years/'.$created['id'], calendarPermissionYearData(['name' => 'Renamed year']))
        ->assertOk()
        ->assertJsonPath('academic_year.name', 'Renamed year');

    $createdYear = AcademicYear::query()->findOrFail((int) $created['id']);
    $period = $this->actingAs($user)
        ->postJson('/api/academic-years/'.$created['id'].'/periods', calendarPermissionPeriodData($createdYear, ['name' => 'Managed period']))
        ->assertCreated()
        ->json('period');

    $this->actingAs($user)
        ->patchJson('/api/academic-periods/'.$period['id'], calendarPermissionPeriodData($createdYear, ['name' => 'Renamed period']))
        ->assertOk()
        ->assertJsonPath('period.name', 'Renamed period');
})->with(['school editor' => 'school_editor', 'school administrator' => 'school_admin']);

it('does not grant calendar permissions from a role assigned in another school', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = calendarPermissionUser($school, null);

    RoleAssignment::query()->forceCreate([
        'school_id' => (int) $otherSchool->id,
        'user_id' => (int) $user->id,
        'role' => 'school_admin',
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    $this->actingAs($user)
        ->postJson('/api/academic-years', calendarPermissionYearData(['name' => 'Cross-school denial']))
        ->assertForbidden();

    $this->assertDatabaseCount('academic_years', 0);
});
