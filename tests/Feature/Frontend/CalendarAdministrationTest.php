<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Frontend\Contracts\CalendarManagementGateway;
use App\Http\Middleware\ResolveSchoolContext;
use App\Livewire\CalendarAdministration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;

uses(RefreshDatabase::class);

beforeEach(fn (): mixed => $this->withoutVite());

function stageBUserWithSchool(School $school, ?string $role = 'school_editor'): User
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

    app(SchoolContext::class)->set($school);
    Auth::login($user);

    return $user;
}

it('requires authentication for calendar administration', function (): void {
    $this->get('/app/academic-calendar/manage')->assertRedirect('/login');
});

it('links to administration from the read-only calendar and scopes the management screen', function (): void {
    $school = School::factory()->create(['name' => 'Riverside High']);
    $other = School::factory()->create(['name' => 'Other Academy']);
    stageBUserWithSchool($school);

    $year = AcademicYear::factory()->for($school)->create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);
    AcademicPeriod::factory()->inYear($year)->create(['name' => 'First term']);
    AcademicYear::factory()->for($other)->create(['name' => '2031']);

    $this->get('/app/academic-calendar')
        ->assertOk()
        ->assertSee('Manage calendar');

    $this->get('/app/academic-calendar/manage')
        ->assertOk()
        ->assertSee('2027')
        ->assertSee('First term')
        ->assertDontSee('2031');
});

it('creates an academic year through the calendar adapter', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school);

    Livewire::test(CalendarAdministration::class)
        ->set('yearName', '2028')
        ->set('yearStartDate', '2028-01-01')
        ->set('yearEndDate', '2028-12-31')
        ->set('yearSource', 'School circular 2028')
        ->call('saveYear')
        ->assertSet('successMessage', 'Academic year added.');

    $this->assertDatabaseHas('academic_years', [
        'school_id' => $school->id,
        'name' => '2028',
        'source' => 'School circular 2028',
        'status' => 'draft',
    ]);
});

it('surfaces validation errors without saving an invalid academic year', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school);

    Livewire::test(CalendarAdministration::class)
        ->set('yearName', '2029')
        ->set('yearStartDate', '2029-12-31')
        ->set('yearEndDate', '2029-01-01')
        ->set('yearSource', '')
        ->call('saveYear')
        ->assertSet('formError', 'Check the highlighted fields and try again.')
        ->assertSet('yearErrors.source', fn (mixed $message): bool => is_string($message) && $message !== '');

    $this->assertDatabaseMissing('academic_years', ['school_id' => $school->id, 'name' => '2029']);
});

it('creates a period in the selected year under server-side tenancy', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school);

    $year = AcademicYear::factory()->for($school)->create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    Livewire::test(CalendarAdministration::class)
        ->set('selectedYearId', $year->id)
        ->set('periodName', 'Term 1')
        ->set('periodType', 'term')
        ->set('periodSequence', '1')
        ->set('periodStartDate', '2027-01-10')
        ->set('periodEndDate', '2027-04-10')
        ->set('periodSource', 'School calendar register')
        ->call('savePeriod')
        ->assertSet('successMessage', 'Period added.');

    $this->assertDatabaseHas('academic_periods', [
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'name' => 'Term 1',
        'sequence' => 1,
    ]);
});

it('rejects a period outside the selected academic year', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school);

    $year = AcademicYear::factory()->for($school)->create([
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    Livewire::test(CalendarAdministration::class)
        ->set('selectedYearId', $year->id)
        ->set('periodName', 'Outside period')
        ->set('periodStartDate', '2026-12-01')
        ->set('periodEndDate', '2027-01-05')
        ->set('periodSource', 'School calendar register')
        ->call('savePeriod')
        ->assertSet('periodErrors.start_date', fn (mixed $message): bool => is_string($message) && $message !== '');

    $this->assertDatabaseMissing('academic_periods', ['name' => 'Outside period']);
});

it('edits an existing period through the management screen', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school);

    $year = AcademicYear::factory()->for($school)->create([
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);
    $period = AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'First term',
        'sequence' => 1,
        'start_date' => '2027-01-10',
        'end_date' => '2027-04-10',
    ]);

    Livewire::test(CalendarAdministration::class)
        ->set('selectedYearId', $year->id)
        ->call('editPeriod', $period->id)
        ->set('periodName', 'First term revised')
        ->call('savePeriod')
        ->assertSet('successMessage', 'Period updated.');

    $this->assertDatabaseHas('academic_periods', [
        'id' => $period->id,
        'school_id' => $school->id,
        'name' => 'First term revised',
    ]);
});

it('shows configured calendar issues in the validation panel', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school);

    $year = AcademicYear::factory()->for($school)->create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);
    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Outside period',
        'sequence' => 1,
        'start_date' => '2026-01-01',
        'end_date' => '2026-03-31',
    ]);

    Livewire::test(CalendarAdministration::class)
        ->set('selectedYearId', $year->id)
        ->call('validateSelectedYear')
        ->assertSet('validation.valid', false)
        ->assertSee('falls outside its academic year');
});

it('registers the school context resolver as persistent Livewire middleware', function (): void {
    expect(app(PersistentMiddleware::class)->getPersistentMiddleware())
        ->toContain(ResolveSchoolContext::class);
});

it('keeps an authorised school context available during a Livewire update', function (): void {
    $school = School::factory()->create(['name' => 'First School']);
    $other = School::factory()->create(['name' => 'Second School']);
    $user = User::factory()->create();
    $user->schools()->attach([$school->id, $other->id]);

    Route::middleware(['web', 'auth', 'school.context'])
        ->get('/_stage-b-context', fn (SchoolContext $context): JsonResponse => response()->json([
            'school_id' => $context->id(),
        ]));

    $this->actingAs($user)
        ->get('/_stage-b-context?school_id='.$school->id)
        ->assertOk()
        ->assertJson(['school_id' => $school->id]);

    // A Livewire update has no page query string. The resolver must reuse the
    // server-authorised selection and keep it set while the component runs.
    $this->withHeader('X-Livewire', 'true')
        ->get('/_stage-b-context')
        ->assertOk()
        ->assertJson(['school_id' => $school->id]);
});

it('rejects a remembered Livewire school context after membership is removed', function (): void {
    $school = School::factory()->create(['name' => 'Former School']);
    $other = School::factory()->create(['name' => 'Current School']);
    $user = User::factory()->create();
    $user->schools()->attach([$school->id, $other->id]);

    Route::middleware(['web', 'auth', 'school.context'])
        ->get('/_stage-b-stale-context', fn (SchoolContext $context): JsonResponse => response()->json([
            'school_id' => $context->id(),
        ]));

    $this->actingAs($user)
        ->get('/_stage-b-stale-context?school_id='.$school->id)
        ->assertOk()
        ->assertJson(['school_id' => $school->id]);

    $user->schools()->detach($school->id);

    $this->withHeader('X-Livewire', 'true')
        ->get('/_stage-b-stale-context')
        ->assertForbidden();
});

it('cannot resolve a year from another school through the calendar adapter', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    stageBUserWithSchool($school);

    $foreignYear = AcademicYear::factory()->for($other)->create();

    expect(fn (): array => app(CalendarManagementGateway::class)->updateYear($foreignYear->id, [
        'name' => 'Changed elsewhere',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'source' => 'Foreign record',
    ]))->toThrow(ModelNotFoundException::class);
});

it('requires the calendar manage permission for the management screen', function (): void {
    $school = School::factory()->create(['name' => 'Permission School']);
    $user = stageBUserWithSchool($school, 'school_member');

    $this->actingAs($user)
        ->get('/app/academic-calendar')
        ->assertOk();

    $this->actingAs($user)
        ->get('/app/academic-calendar/manage')
        ->assertForbidden();
});

it('denies Livewire calendar writes without the calendar manage permission', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school, 'school_member');

    Livewire::test(CalendarAdministration::class)
        ->set('yearName', 'Unauthorised year')
        ->set('yearStartDate', '2029-01-01')
        ->set('yearEndDate', '2029-12-31')
        ->set('yearSource', 'School circular 2029')
        ->call('saveYear')
        ->assertSet('formError', 'This academic year cannot be changed in the current school context.');

    $this->assertDatabaseMissing('academic_years', [
        'school_id' => $school->id,
        'name' => 'Unauthorised year',
    ]);
});

it('enforces the calendar manage permission in the frontend gateway', function (): void {
    $school = School::factory()->create();
    stageBUserWithSchool($school, null);

    expect(fn (): array => app(CalendarManagementGateway::class)->createYear([
        'name' => 'Gateway denial',
        'start_date' => '2030-01-01',
        'end_date' => '2030-12-31',
        'source' => 'School circular 2030',
    ]))->toThrow(AuthorizationException::class);

    $this->assertDatabaseMissing('academic_years', ['name' => 'Gateway denial']);
});

it('rejects an unowned school selection on the calendar management route', function (): void {
    $owned = School::factory()->create(['name' => 'Owned School']);
    $other = School::factory()->create(['name' => 'Other School']);
    $user = stageBUserWithSchool($owned);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $other->id)
        ->get('/app/academic-calendar/manage')
        ->assertForbidden()
        ->assertDontSee('Other School');
});
