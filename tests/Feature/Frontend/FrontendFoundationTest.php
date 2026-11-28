<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolIdentifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// CI's tests job does not build assets, so Blade's @vite directive must not
// require a manifest for these view-rendering assertions.
beforeEach(fn (): mixed => $this->withoutVite());

function frontendUserWithSchool(School $school, ?string $role = 'school_member'): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);
    grantSchoolRole($user, $school);

    if ($role !== null) {
        RoleAssignment::query()->forceCreate([
            'school_id' => (int) $school->id,
            'user_id' => (int) $user->id,
            'role' => $role,
            'source' => 'domain',
            'created_at' => now(),
        ]);
    }

    return $user;
}

it('redirects guests from the application to the sign-in screen', function (): void {
    $this->get('/app')->assertRedirect('/login');
    $this->get('/login')->assertOk()->assertSee('Sign in to your school');
});

it('renders the sign-in form without exposing credential values', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('data-api-login', false)
        ->assertSee('name="password"', false)
        ->assertSee('type="password" value=""', false);
});

it('requires an authorised school context for application screens', function (): void {
    $this->actingAs(User::factory()->create())->get('/app')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/app/school')->assertForbidden();
});

it('renders real school and calendar data on the dashboard', function (): void {
    $school = School::factory()->create(['name' => 'Chinhoyi High School']);
    $user = frontendUserWithSchool($school);
    $year = AcademicYear::factory()->for($school)->create([
        'name' => '2026',
        'status' => 'published',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertOk()
        ->assertSee('Chinhoyi High School')
        ->assertSee('2026')
        ->assertSee('Available now')
        ->assertDontSee('Demo data');
});

it('renders a parseable Alpine navigation expression in the app shell', function (): void {
    $school = School::factory()->create();
    $user = frontendUserWithSchool($school);

    $this->actingAs($user)
        ->get('/app')
        ->assertOk()
        ->assertSee("[tabindex]:not([tabindex='-1'])", false)
        ->assertDontSee('[tabindex]:not([tabindex="-1"])', false)
        ->assertSee('openMenu(event)', false)
        ->assertSee('@click="openMenu($event)"', false)
        ->assertDontSee('this.menuOpener = $event.currentTarget', false);
});

it('labels demo figures so they cannot be mistaken for school records', function (): void {
    config()->set('frontend.demo.enabled', true);

    $school = School::factory()->create(['name' => 'Demo Lab School']);
    $user = frontendUserWithSchool($school);

    $this->actingAs($user)
        ->get('/app')
        ->assertOk()
        ->assertSee('Demo data')
        ->assertSee('Demo data — not school records.');
});

it('shows only identifiers from the authorised school on the profile screen', function (): void {
    $school = School::factory()->create(['name' => 'Authorised School']);
    $other = School::factory()->create(['name' => 'Other School']);
    $user = frontendUserWithSchool($school);

    $own = SchoolIdentifier::factory()->for($school)->create(['value' => 'OWN-100']);
    $foreign = SchoolIdentifier::factory()->for($other)->create(['value' => 'OTHER-900']);

    $this->actingAs($user)
        ->get('/app/school')
        ->assertOk()
        ->assertSee('Authorised School')
        ->assertSee($own->value)
        ->assertDontSee($foreign->value);
});

it('lists calendar years and periods for the selected school only', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = frontendUserWithSchool($school);

    $year = AcademicYear::factory()->for($school)->create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);
    $otherYear = AcademicYear::factory()->for($other)->create([
        'name' => '2031',
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
    ]);
    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Term 1',
        'sequence' => 1,
        'start_date' => '2027-01-01',
        'end_date' => '2027-03-31',
    ]);
    AcademicPeriod::factory()->inYear($otherYear)->create(['name' => 'Foreign term']);

    $this->actingAs($user)
        ->get('/app/academic-calendar?year='.$year->id)
        ->assertOk()
        ->assertSee('2027')
        ->assertSee('Term 1')
        ->assertDontSee('2031')
        ->assertDontSee('Foreign term');
});

it('states plainly when a planned module has no screens', function (): void {
    $school = School::factory()->create();
    $user = frontendUserWithSchool($school);

    $this->actingAs($user)
        ->get('/app/modules/fees')
        ->assertOk()
        ->assertSee('This module is not built yet')
        ->assertSee('Fees');
});

it('renders controller screens inside the application layout', function (): void {
    $school = School::factory()->create();
    $user = frontendUserWithSchool($school);

    foreach (['/app/school', '/app/academic-calendar', '/app/modules/fees'] as $uri) {
        $this->actingAs($user)
            ->get($uri)
            ->assertOk()
            ->assertSee('<!DOCTYPE html>', false)
            ->assertSee('name="csrf-token"', false)
            ->assertSee('· School Manager</title>', false);
    }
});

it('lets deployments hide navigation items from the shell', function (): void {
    config()->set('frontend.hidden_modules', ['fees']);

    $school = School::factory()->create();
    $user = frontendUserWithSchool($school);

    $this->actingAs($user)
        ->get('/app')
        ->assertOk()
        ->assertDontSee('>Fees<', false)
        ->assertSee('Academic calendar');
});

it('keeps the session and CSRF contract on the application shell', function (): void {
    $school = School::factory()->create();
    $user = frontendUserWithSchool($school);

    $this->actingAs($user)
        ->get('/app')
        ->assertOk()
        ->assertSee('name="csrf-token"', false)
        ->assertSee('data-api-logout', false)
        ->assertSee($user->name);
});
