<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Actions\CreateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicYear;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function calendarHttpUser(School $school, ?string $role = 'school_editor'): User
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
function calendarHttpYearData(array $overrides = []): array
{
    return array_merge([
        'name' => 'HTTP year',
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
        'source_reference' => 'calendar-register/2031',
        'source_published_at' => '2030-11-01',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calendarHttpPeriodData(AcademicYear $year, array $overrides = []): array
{
    return array_merge([
        'academic_year_id' => (int) $year->id,
        'name' => 'HTTP period',
        'type' => 'term',
        'sequence' => 1,
        'start_date' => '2031-01-10',
        'end_date' => '2031-03-31',
        'status' => 'published',
        'counting_basis' => 'school_days',
        'source' => 'School calendar register',
        'source_reference' => 'calendar-register/2031',
        'source_published_at' => '2030-11-01',
    ], $overrides);
}

it('requires authentication and school context for calendar endpoints', function (): void {
    $this->getJson('/api/academic-years')->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/academic-years')
        ->assertForbidden();
});

it('creates and lists tenant-scoped academic years through the API', function (): void {
    $school = School::factory()->create();
    $user = calendarHttpUser($school);

    $created = $this->actingAs($user)
        ->postJson('/api/academic-years', calendarHttpYearData(['name' => 'Gateway year']))
        ->assertCreated()
        ->assertJsonPath('academic_year.name', 'Gateway year')
        ->assertJsonPath('academic_year.school_id', $school->id)
        ->assertHeader('Vary', 'X-School-Id, school_id');

    expect($created->headers->get('Cache-Control'))->toContain('no-store');

    $this->actingAs($user)
        ->getJson('/api/academic-years')
        ->assertOk()
        ->assertJsonCount(1, 'academic_years')
        ->assertJsonPath('academic_years.0.id', $created->json('academic_year.id'))
        ->assertJsonPath('school_id', $school->id);
});

it('creates and lists ordered periods for one academic year through the API', function (): void {
    $school = School::factory()->create();
    $user = calendarHttpUser($school);
    $year = AcademicYear::factory()->for($school)->create(calendarHttpYearData([
        'name' => 'Ordered year',
        'source' => 'School calendar register',
    ]));

    $this->actingAs($user)
        ->postJson('/api/academic-years/'.$year->id.'/periods', calendarHttpPeriodData($year, [
            'name' => 'Later block',
            'sequence' => 2,
            'start_date' => '2031-07-01',
            'end_date' => '2031-09-30',
        ]))
        ->assertCreated();

    $this->actingAs($user)
        ->postJson('/api/academic-years/'.$year->id.'/periods', calendarHttpPeriodData($year, [
            'name' => 'Earlier block',
            'sequence' => 1,
        ]))
        ->assertCreated()
        ->assertJsonPath('period.academic_year_id', $year->id)
        ->assertHeader('Vary', 'X-School-Id, school_id');

    $this->actingAs($user)
        ->getJson('/api/academic-years/'.$year->id.'/periods')
        ->assertOk()
        ->assertJsonCount(2, 'periods')
        ->assertJsonPath('periods.0.sequence', 1)
        ->assertJsonPath('periods.0.name', 'Earlier block')
        ->assertJsonPath('periods.1.sequence', 2)
        ->assertJsonPath('academic_year_id', $year->id);
});

it('rejects client supplied school identifiers on calendar writes', function (string $endpoint, array $payload): void {
    $school = School::factory()->create();
    $user = calendarHttpUser($school);

    $payload['school_id'] = $school->id;

    $this->actingAs($user)
        ->postJson($endpoint, $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');

    $this->assertDatabaseCount('academic_years', 0)
        ->assertDatabaseCount('academic_periods', 0);
})->with([
    'academic year' => [
        '/api/academic-years',
        calendarHttpYearData(['name' => 'Rejected year']),
    ],
    'academic period' => [
        '/api/academic-years/1/periods',
        [
            'academic_year_id' => 1,
            'name' => 'Rejected period',
            'type' => 'term',
            'sequence' => 1,
            'start_date' => '2031-01-10',
            'end_date' => '2031-03-31',
            'status' => 'published',
            'counting_basis' => 'school_days',
            'source' => 'School calendar register',
        ],
    ],
]);

it('uses the route year instead of a client supplied year for period creation', function (): void {
    $school = School::factory()->create();
    $user = calendarHttpUser($school);
    $routeYear = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Route year']));
    $clientYear = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Client year']));

    $payload = calendarHttpPeriodData($routeYear, [
        'name' => 'Route wins',
        'academic_year_id' => $clientYear->id,
    ]);

    $this->actingAs($user)
        ->postJson('/api/academic-years/'.$routeYear->id.'/periods', $payload)
        ->assertCreated()
        ->assertJsonPath('period.academic_year_id', $routeYear->id);

    $this->assertDatabaseHas('academic_periods', [
        'name' => 'Route wins',
        'academic_year_id' => $routeYear->id,
        'school_id' => $school->id,
    ]);
});

it('does not expose or allow updates to another school calendar records', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = calendarHttpUser($school);

    $ownedYear = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Owned year']));
    $otherYear = AcademicYear::factory()->for($otherSchool)->create(calendarHttpYearData(['name' => 'Other year']));
    $otherPeriod = AcademicPeriod::factory()->inYear($otherYear)->create(['name' => 'Other period']);

    $this->actingAs($user)
        ->getJson('/api/academic-years')
        ->assertOk()
        ->assertJsonCount(1, 'academic_years')
        ->assertJsonPath('academic_years.0.id', $ownedYear->id);

    $this->actingAs($user)
        ->getJson('/api/academic-years/'.$otherYear->id.'/periods')
        ->assertNotFound();

    $this->actingAs($user)
        ->putJson('/api/academic-years/'.$otherYear->id, calendarHttpYearData(['name' => 'Tampered year']))
        ->assertNotFound();

    $this->actingAs($user)
        ->putJson('/api/academic-periods/'.$otherPeriod->id, calendarHttpPeriodData($ownedYear, ['name' => 'Tampered period']))
        ->assertNotFound();

    $this->actingAs($user)
        ->getJson('/api/academic-calendar/validation?academic_year_id='.$otherYear->id)
        ->assertNotFound();
});

it('rejects an authorised school selection the user cannot access', function (): void {
    $owned = School::factory()->create();
    $other = School::factory()->create();
    $user = calendarHttpUser($owned);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $other->id)
        ->getJson('/api/academic-years')
        ->assertForbidden();
});

it('requires an explicit school selection for multi-school calendar access', function (): void {
    $selected = School::factory()->create();
    $user = calendarHttpUser($selected, 'school_member');
    $user->schools()->attach(School::factory()->create());

    $this->actingAs($user)
        ->getJson('/api/academic-years')
        ->assertBadRequest();

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $selected->id)
        ->getJson('/api/academic-years')
        ->assertOk()
        ->assertJsonPath('school_id', $selected->id);
});

it('fails closed for unscoped calendar queries and cross-school domain writes', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $otherYear = AcademicYear::factory()->for($otherSchool)->create(calendarHttpYearData(['name' => 'Outside year']));
    $otherPeriod = AcademicPeriod::factory()->inYear($otherYear)->create(['name' => 'Outside period']);

    expect(fn (): int => AcademicYear::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class)
        ->and(fn (): int => AcademicPeriod::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(CreateAcademicPeriod::class)->execute($school, calendarHttpPeriodData($otherYear)))
        ->toThrow(ValidationException::class);

    expect(fn () => app(UpdateAcademicYear::class)->execute($school, $otherYear, calendarHttpYearData(['name' => 'Changed'])))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UpdateAcademicPeriod::class)->execute($school, $otherPeriod, calendarHttpPeriodData($otherYear, ['name' => 'Changed'])))
        ->toThrow(AuthorizationException::class);
});

it('validates one scoped academic year and rejects a client supplied school id', function (): void {
    $school = School::factory()->create();
    $user = calendarHttpUser($school);
    $year = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Valid year']));
    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Valid period',
        'start_date' => '2031-01-10',
        'end_date' => '2031-03-31',
    ]);

    $this->actingAs($user)
        ->getJson('/api/academic-calendar/validation?academic_year_id='.$year->id)
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('academic_year_id', $year->id)
        ->assertJsonCount(0, 'issues');

    $this->actingAs($user)
        ->getJson('/api/academic-calendar/validation?academic_year_id='.$year->id.'&school_id='.$school->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');
});

it('rejects a client supplied school id on calendar listing endpoints', function (string $path): void {
    $school = School::factory()->create();
    $user = calendarHttpUser($school);
    $year = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Listed year']));

    $path = str_replace('{year}', (string) $year->id, $path);

    $this->actingAs($user)
        ->getJson($path)
        ->assertOk();

    $this->actingAs($user)
        ->getJson($path.'?school_id='.$school->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');
})->with([
    'years' => ['/api/academic-years'],
    'periods' => ['/api/academic-years/{year}/periods'],
]);

it('does not reveal foreign year identifiers when creating a period', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = calendarHttpUser($school);
    $ownedYear = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Own year']));
    $foreignYear = AcademicYear::factory()->for($otherSchool)->create(calendarHttpYearData(['name' => 'Foreign year']));

    $response = $this->actingAs($user)
        ->postJson('/api/academic-years/'.$ownedYear->id.'/periods', calendarHttpPeriodData($ownedYear, [
            'academic_year_id' => $foreignYear->id,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('academic_year_id');

    expect((string) json_encode($response->json()))->not->toContain('does not belong to this school');

    $this->assertDatabaseCount('academic_periods', 0);
});

it('does not reveal foreign year identifiers when reassigning a period', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = calendarHttpUser($school);
    $ownedYear = AcademicYear::factory()->for($school)->create(calendarHttpYearData(['name' => 'Own year']));
    $foreignYear = AcademicYear::factory()->for($otherSchool)->create(calendarHttpYearData(['name' => 'Foreign year']));
    $period = AcademicPeriod::factory()->inYear($ownedYear)->create([
        'name' => 'Own period',
        'start_date' => '2031-01-10',
        'end_date' => '2031-03-31',
    ]);

    $response = $this->actingAs($user)
        ->putJson('/api/academic-periods/'.$period->id, calendarHttpPeriodData($ownedYear, [
            'academic_year_id' => $foreignYear->id,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('academic_year_id');

    expect((string) json_encode($response->json()))->not->toContain('does not belong to this school');

    $this->assertDatabaseHas('academic_periods', [
        'id' => $period->id,
        'academic_year_id' => $ownedYear->id,
        'name' => 'Own period',
    ]);
});

it('resolves the current year only for the authorised school and rejects client school input', function (): void {
    Carbon::setTestNow(Carbon::parse('2031-06-15'));

    try {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $user = calendarHttpUser($school);

        $current = AcademicYear::factory()->for($school)->create(calendarHttpYearData([
            'name' => 'Current year',
            'start_date' => '2031-01-01',
            'end_date' => '2031-12-31',
        ]));
        AcademicYear::factory()->for($otherSchool)->create(calendarHttpYearData([
            'name' => 'Foreign current year',
            'start_date' => '2031-05-01',
            'end_date' => '2031-12-31',
        ]));

        $this->actingAs($user)
            ->getJson('/api/academic-years/current')
            ->assertOk()
            ->assertJsonPath('school_id', $school->id)
            ->assertJsonPath('academic_year.id', $current->id);

        $this->actingAs($user)
            ->getJson('/api/academic-years/current?school_id='.$otherSchool->id)
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson('/api/academic-years/current?school_id='.$school->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('school_id');
    } finally {
        Carbon::setTestNow();
    }
});

it('returns an explicit null current year when no year qualifies', function (): void {
    Carbon::setTestNow(Carbon::parse('2031-06-15'));

    try {
        $school = School::factory()->create();
        $user = calendarHttpUser($school);
        AcademicYear::factory()->for($school)->create(calendarHttpYearData([
            'name' => 'Future year',
            'start_date' => '2032-01-01',
            'end_date' => '2032-12-31',
        ]));

        $this->actingAs($user)
            ->getJson('/api/academic-years/current')
            ->assertOk()
            ->assertJsonPath('school_id', $school->id)
            ->assertJsonPath('academic_year', null);
    } finally {
        Carbon::setTestNow();
    }
});
