<?php

declare(strict_types=1);

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Livewire\RoleAssignments;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn (): mixed => $this->withoutVite());

function roleScreenActorWithSchool(School $school, ?string $role): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    if ($role !== null) {
        RoleAssignment::factory()->for($school)->for($user)->create(['role' => $role]);
    }

    app(SchoolContext::class)->set($school);
    Auth::login($user);

    return $user;
}

it('requires authentication for role assignments', function (): void {
    $this->get('/app/roles')->assertRedirect('/login');
});

it('uses only verified assignment fields on the role screen', function (): void {
    $school = School::factory()->create(['name' => 'Gweru High']);
    roleScreenActorWithSchool($school, 'school_admin');

    $this->get('/app/roles')
        ->assertOk()
        ->assertSee('Gweru High')
        ->assertSee('Assign a role')
        ->assertSee('User ID')
        ->assertSee('Current assignments')
        ->assertDontSee('name="school_id"', false)
        ->assertDontSee('name="school_members"', false);
});

it('lets an authorised actor assign a configured role through the screen', function (): void {
    $school = School::factory()->create();
    roleScreenActorWithSchool($school, 'school_admin');

    $target = User::factory()->create();
    $target->schools()->attach($school);

    Livewire::test(RoleAssignments::class)
        ->set('userId', $target->id)
        ->set('role', 'role_viewer')
        ->call('assign')
        ->assertSet('successMessage', 'Role assigned.');

    $this->assertDatabaseHas('role_assignments', [
        'school_id' => $school->id,
        'user_id' => $target->id,
        'role' => 'role_viewer',
        'source' => 'domain',
    ]);
});

it('does not offer assignment controls to an account without manage permission', function (): void {
    $school = School::factory()->create();
    roleScreenActorWithSchool($school, 'role_viewer');

    $target = User::factory()->create();
    $target->schools()->attach($school);

    Livewire::test(RoleAssignments::class)
        ->assertSee('Your account can view assignments but cannot add or remove them')
        ->set('userId', $target->id)
        ->set('role', 'school_member')
        ->call('assign')
        ->assertSet('formError', 'Your account cannot manage role assignments in this school.');

    $this->assertDatabaseMissing('role_assignments', ['user_id' => $target->id]);
});

it('requires roles.view to open the role screen', function (?string $role, int $status): void {
    $school = School::factory()->create();
    roleScreenActorWithSchool($school, $role);

    $this->get('/app/roles')->assertStatus($status);
})->with([
    'roleless member' => [null, 403],
    'school member' => ['school_member', 403],
    'role viewer' => ['role_viewer', 200],
    'school admin' => ['school_admin', 200],
]);

it('does not expose assignments through the component without view permission', function (): void {
    $school = School::factory()->create();
    roleScreenActorWithSchool($school, 'school_member');

    $foreignUser = User::factory()->create();
    RoleAssignment::factory()->for($school)->for($foreignUser)->create(['role' => 'role_viewer']);

    Livewire::test(RoleAssignments::class)
        ->assertSee('Not available to this account')
        ->assertDontSee('>'.$foreignUser->id.'</td>', false);
});

it('removes an assignment scoped to the authorised school', function (): void {
    $school = School::factory()->create();
    roleScreenActorWithSchool($school, 'school_admin');

    $target = User::factory()->create();
    $target->schools()->attach($school);
    $assignment = RoleAssignment::factory()->for($school)->for($target)->create(['role' => 'role_viewer']);

    Livewire::test(RoleAssignments::class)
        ->call('remove', $assignment->id)
        ->assertSet('successMessage', 'Role assignment removed.');

    $this->assertDatabaseMissing('role_assignments', ['id' => $assignment->id]);
});
