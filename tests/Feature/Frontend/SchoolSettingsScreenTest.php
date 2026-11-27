<?php

declare(strict_types=1);

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Livewire\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn (): mixed => $this->withoutVite());

function settingsScreenActorWithSchool(School $school): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);
    app(SchoolContext::class)->set($school);
    Auth::login($user);

    return $user;
}

it('requires authentication for school settings', function (): void {
    $this->get('/app/school-settings')->assertRedirect('/login');
});

it('shows verified defaults without creating a settings record', function (): void {
    $school = School::factory()->create(['name' => 'Marondera School']);
    settingsScreenActorWithSchool($school);

    $this->get('/app/school-settings')
        ->assertOk()
        ->assertSee('Marondera School')
        ->assertSee('No saved preferences yet')
        ->assertSee('Regional preferences')
        ->assertSee('Revision history')
        ->assertDontSee('name="school_id"', false)
        ->assertDontSee('name="password"', false);

    $this->assertDatabaseCount('school_settings', 0)
        ->assertDatabaseCount('school_setting_revisions', 0);
});

it('saves only contract fields and records provenance through the screen', function (): void {
    $school = School::factory()->create();
    settingsScreenActorWithSchool($school);

    Livewire::test(SchoolSettings::class)
        ->set('timezone', 'Africa/Harare')
        ->set('source', 'School circular 2031')
        ->set('effectiveFrom', '2031-01-15')
        ->call('save')
        ->assertSet('successMessage', 'School settings saved.');

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'locale' => 'en',
        'timezone' => 'Africa/Harare',
        'source' => 'School circular 2031',
        'effective_from' => '2031-01-15',
        'version' => 1,
    ])->assertDatabaseCount('school_setting_revisions', 0);
});

it('creates a revision when saved preferences change again', function (): void {
    $school = School::factory()->create();
    settingsScreenActorWithSchool($school);

    Livewire::test(SchoolSettings::class)
        ->set('timezone', 'Africa/Harare')
        ->set('source', 'Initial configuration')
        ->set('effectiveFrom', '2031-01-01')
        ->call('save')
        ->set('timezone', 'UTC')
        ->set('source', 'Principal approval')
        ->set('effectiveFrom', '2031-02-01')
        ->call('save')
        ->assertSet('successMessage', 'School settings saved.');

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'timezone' => 'UTC',
        'version' => 2,
    ])->assertDatabaseHas('school_setting_revisions', [
        'school_id' => $school->id,
        'version' => 1,
        'source' => 'Initial configuration',
    ]);
});

it('surfaces validation errors instead of silently saving invalid settings', function (): void {
    $school = School::factory()->create();
    settingsScreenActorWithSchool($school);

    Livewire::test(SchoolSettings::class)
        ->set('source', '   ')
        ->call('save')
        ->assertSet('errorsByField.source', fn (mixed $message): bool => is_string($message) && $message !== '')
        ->assertSet('formError', 'Check the highlighted fields and try again.');

    $this->assertDatabaseCount('school_settings', 0);
});
