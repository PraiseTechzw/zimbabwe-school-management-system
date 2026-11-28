<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolIdentifier;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\ResolveSchoolContext;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn (): mixed => $this->withoutVite());

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function b04SettingsPayload(array $overrides = []): array
{
    return array_merge([
        'locale' => 'en',
        'timezone' => 'Africa/Harare',
        'source' => 'School configuration register',
        'effective_from' => '2031-03-01',
    ], $overrides);
}

it('gives members read-only school settings and editors write access', function (): void {
    $school = School::factory()->create();

    $member = User::factory()->create();
    $member->schools()->attach($school);
    grantSchoolRole($member, $school, 'school_member');

    $editor = User::factory()->create();
    $editor->schools()->attach($school);
    grantSchoolRole($editor, $school, 'school_editor');

    $this->actingAs($member)
        ->getJson('/api/school-settings')
        ->assertOk()
        ->assertJsonPath('settings.timezone', 'UTC');
    $this->actingAs($member)->getJson('/api/school-settings/revisions')->assertOk();
    $this->actingAs($member)->putJson('/api/school-settings', b04SettingsPayload())->assertForbidden();

    $this->actingAs($editor)
        ->putJson('/api/school-settings', b04SettingsPayload())
        ->assertOk()
        ->assertJsonPath('settings.timezone', 'Africa/Harare');

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'timezone' => 'Africa/Harare',
    ]);
});

it('denies school settings to a role viewer without settings permissions', function (): void {
    $school = School::factory()->create();
    $viewer = User::factory()->create();
    $viewer->schools()->attach($school);
    grantSchoolRole($viewer, $school, 'role_viewer');

    $this->actingAs($viewer)->getJson('/api/school-settings')->assertForbidden();
    $this->actingAs($viewer)->getJson('/api/school-settings/revisions')->assertForbidden();
    $this->actingAs($viewer)->putJson('/api/school-settings', b04SettingsPayload())->assertForbidden();

    $this->assertDatabaseCount('school_settings', 0);
});

it('enforces catalogued read permissions on school and calendar endpoints', function (string $endpoint, ?string $role, int $status): void {
    $school = School::factory()->create();
    $user = User::factory()->create();
    $user->schools()->attach($school);

    if ($role !== null) {
        grantSchoolRole($user, $school, $role);
    }

    $this->actingAs($user)->getJson($endpoint)->assertStatus($status);
})->with([
    'school context requires school.view' => ['/api/school', null, 403],
    'identifiers require school_identifiers.view' => ['/api/school/identifiers', 'role_viewer', 403],
    'calendar years require academic_calendar.view' => ['/api/academic-years', 'role_viewer', 403],
    'calendar validation requires academic_calendar.view' => ['/api/academic-calendar/validation', 'role_viewer', 403],
    'calendar screen requires academic_calendar.view' => ['/app/academic-calendar', 'role_viewer', 403],
    'structure screen requires academic_structure.view' => ['/app/academic-structure', 'role_viewer', 403],
    'settings screen requires school_settings.view' => ['/app/school-settings', 'role_viewer', 403],
    'roles screen requires roles.view' => ['/app/roles', 'school_member', 403],
    'members may read school context' => ['/api/school', 'school_member', 200],
    'members may read identifiers' => ['/api/school/identifiers', 'school_member', 200],
    'members may read calendar years' => ['/api/academic-years', 'school_member', 200],
    'members may read the structure screen' => ['/app/academic-structure', 'school_member', 200],
    'members may read the settings screen' => ['/app/school-settings', 'school_member', 200],
    'role viewers may read the roles screen' => ['/app/roles', 'role_viewer', 200],
]);

it('does not leak identifiers or calendar records to a role viewer on frontend screens', function (): void {
    $school = School::factory()->create(['name' => 'Chinhoyi High School']);
    $viewer = User::factory()->create();
    $viewer->schools()->attach($school);
    grantSchoolRole($viewer, $school, 'role_viewer');

    SchoolIdentifier::factory()->for($school)->create(['value' => 'VIEWER-SECRET-ID']);
    AcademicYear::factory()->for($school)->create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    $this->actingAs($viewer)
        ->get('/app')
        ->assertOk()
        ->assertSee('Chinhoyi High School')
        ->assertDontSee('VIEWER-SECRET-ID')
        ->assertDontSee('2027');

    $this->actingAs($viewer)
        ->get('/app/school')
        ->assertOk()
        ->assertSee('Chinhoyi High School')
        ->assertDontSee('VIEWER-SECRET-ID');
});

it('hides the calendar management link from members and shows it to editors', function (): void {
    $school = School::factory()->create();

    $member = User::factory()->create();
    $member->schools()->attach($school);
    grantSchoolRole($member, $school, 'school_member');

    $editor = User::factory()->create();
    $editor->schools()->attach($school);
    grantSchoolRole($editor, $school, 'school_editor');

    $this->actingAs($member)
        ->get('/app/academic-calendar')
        ->assertOk()
        ->assertDontSee('Manage calendar')
        ->assertDontSee(route('frontend.calendar-manage'));

    $this->actingAs($editor)
        ->get('/app/academic-calendar')
        ->assertOk()
        ->assertSee('Manage calendar');
});

it('shows only navigation links the actor is permitted to open', function (): void {
    $school = School::factory()->create();

    $member = User::factory()->create();
    $member->schools()->attach($school);
    grantSchoolRole($member, $school, 'school_member');

    $viewer = User::factory()->create();
    $viewer->schools()->attach($school);
    grantSchoolRole($viewer, $school, 'role_viewer');

    $this->actingAs($member)
        ->get('/app')
        ->assertOk()
        ->assertSee(route('frontend.academic-structure'), false)
        ->assertSee(route('frontend.school-settings'), false)
        ->assertDontSee(route('frontend.roles'), false);

    $this->actingAs($viewer)
        ->get('/app')
        ->assertOk()
        ->assertDontSee(route('frontend.academic-structure'), false)
        ->assertDontSee(route('frontend.school-settings'), false)
        ->assertSee(route('frontend.roles'), false);
});

it('replays route permission middleware on Livewire updates', function (): void {
    expect(Livewire::getPersistentMiddleware())
        ->toContain(EnsurePermission::class)
        ->toContain(ResolveSchoolContext::class);
});

it('fails closed when an out-of-band caller uses the authorization contract without permission', function (): void {
    $school = School::factory()->create();
    $user = User::factory()->create();
    $user->schools()->attach($school);

    expect(fn (): mixed => app(SchoolPermissions::class)
        ->authorize($user, $school, 'school.view'))->toThrow(AuthorizationException::class);

    grantSchoolRole($user, $school, 'school_member');

    expect(fn (): mixed => app(SchoolPermissions::class)
        ->authorize($user, $school, 'school.view'))->not->toThrow(AuthorizationException::class);

    expect(fn (): mixed => app(SchoolPermissions::class)
        ->authorize($user, $school, 'school_settings.manage'))->toThrow(AuthorizationException::class);
});
