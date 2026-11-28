<?php

declare(strict_types=1);

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Models\School;
use App\Http\Middleware\EnsurePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function learnerPermissionUser(School $school, ?string $role): User
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

it('denies learner reads and writes to a user without a role assignment', function (): void {
    $school = School::factory()->create();
    $user = learnerPermissionUser($school, null);

    $this->actingAs($user)->getJson('/api/learners')->assertForbidden();
    $this->actingAs($user)
        ->postJson('/api/learners', ['full_name' => 'Denied Learner'])
        ->assertForbidden();

    expect(Learner::query()->forSchool($school)->count())->toBe(0);
});

it('does not grant learner access through the role viewer role', function (): void {
    $school = School::factory()->create();
    $user = learnerPermissionUser($school, 'role_viewer');

    $this->actingAs($user)->getJson('/api/learners')->assertForbidden();
    $this->actingAs($user)
        ->postJson('/api/learners', ['full_name' => 'Denied Learner'])
        ->assertForbidden();
});

it('does not grant learner access through existing staff roles', function (string $role): void {
    $school = School::factory()->create();
    $user = learnerPermissionUser($school, $role);
    $learner = Learner::factory()->for($school)->create();

    $this->actingAs($user)->getJson('/api/learners')->assertForbidden();
    $this->actingAs($user)->getJson('/api/learners/'.$learner->id)->assertForbidden();
    $this->actingAs($user)
        ->patchJson('/api/learners/'.$learner->id, ['full_name' => 'Denied Learner'])
        ->assertForbidden();

    expect($learner->fresh()->full_name)->not->toBe('Denied Learner');
})->with(['school_member', 'school_editor']);

it('lets school administrators read and write learners through the wildcard role', function (): void {
    $school = School::factory()->create();
    $user = learnerPermissionUser($school, 'school_admin');

    $this->actingAs($user)->getJson('/api/learners')->assertOk();

    $learner = $this->actingAs($user)
        ->postJson('/api/learners', ['full_name' => 'Admin Created Learner'])
        ->assertCreated()
        ->json('learner');

    expect($learner)->not->toBeNull();

    $this->actingAs($user)
        ->patchJson('/api/learners/'.$learner['id'], ['full_name' => 'Admin Corrected Learner'])
        ->assertOk()
        ->assertJsonPath('learner.full_name', 'Admin Corrected Learner');
});

it('does not grant learner permissions from a role assigned in another school', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = learnerPermissionUser($school, null);

    RoleAssignment::query()->forceCreate([
        'school_id' => (int) $other->id,
        'user_id' => (int) $user->id,
        'role' => 'school_admin',
        'source' => 'domain',
        'created_at' => Carbon::now(),
    ]);

    $this->actingAs($user)->getJson('/api/learners')->assertForbidden();

    expect(Learner::query()->forSchool($school)->count())->toBe(0);
});

it('applies the expected permission to every registered learner route', function (): void {
    $expected = [
        'GET api/learners' => 'learners.view',
        'POST api/learners' => 'learners.manage',
        'GET api/learners/{learner}' => 'learners.view',
        'PUT api/learners/{learner}' => 'learners.manage',
        'PATCH api/learners/{learner}' => 'learners.manage',
    ];

    $actual = [];

    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();

        if (! str_starts_with($uri, 'api/learners')) {
            continue;
        }

        $middleware = collect($route->gatherMiddleware())->first(
            fn (mixed $value): bool => is_string($value)
                && str_starts_with($value, EnsurePermission::class.':'),
        );

        expect($middleware)->not->toBeNull();

        foreach ($route->methods() as $method) {
            if ($method === 'HEAD' || $method === 'OPTIONS') {
                continue;
            }

            $actual[$method.' '.$uri] = explode(':', (string) $middleware, 2)[1];
        }
    }

    ksort($expected);
    ksort($actual);

    expect($actual)->toBe($expected);
});
