<?php

declare(strict_types=1);

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolIdentifier;
use App\Domains\School\Models\SchoolRelationship;
use App\Domains\School\Models\SchoolStatus;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schoolContextUser(School ...$schools): User
{
    $user = User::factory()->create();

    foreach ($schools as $school) {
        $user->schools()->attach($school);
        grantSchoolRole($user, $school);
    }

    return $user;
}

it('rejects an unauthenticated request before resolving a school', function (): void {
    $this->getJson('/api/school')->assertUnauthorized();

    expect(app(SchoolContext::class)->current())->toBeNull();
});

it('rejects an authenticated user with no school membership', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/school')
        ->assertForbidden();
});

it('uses the only school of a single-school user without client input', function (): void {
    $school = School::factory()->create();
    $user = schoolContextUser($school);

    $this->actingAs($user)
        ->getJson('/api/school')
        ->assertOk()
        ->assertJsonPath('school.id', $school->id)
        ->assertJsonPath('school.name', $school->name);
});

it('requires an explicit selection when a user belongs to several schools', function (): void {
    $user = User::factory()->create();
    $user->schools()->attach(School::factory()->create());
    $user->schools()->attach(School::factory()->create());

    $this->actingAs($user)
        ->getJson('/api/school')
        ->assertBadRequest();
});

it('returns only the selected school identifiers from an authorised multi-school user', function (): void {
    $selected = School::factory()->create();
    $other = School::factory()->create();

    $selectedIdentifier = SchoolIdentifier::factory()->for($selected)->create();
    $otherIdentifier = SchoolIdentifier::factory()->for($other)->create();

    $user = schoolContextUser($selected, $other);

    $response = $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $selected->id)
        ->getJson('/api/school/identifiers');

    $response->assertOk()
        ->assertJsonPath('school_id', $selected->id)
        ->assertJsonCount(1, 'identifiers')
        ->assertJsonPath('identifiers.0.id', $selectedIdentifier->id)
        ->assertJsonMissing(['id' => $otherIdentifier->id]);
});

it('ignores a client-supplied school id that the user cannot access', function (): void {
    $owned = School::factory()->create();
    $unowned = School::factory()->create();

    $user = User::factory()->create();
    $user->schools()->attach($owned);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $unowned->id)
        ->getJson('/api/school')
        ->assertForbidden();
});

it('rejects an unauthorised school id supplied in the query string', function (): void {
    $owned = School::factory()->create();
    $unowned = School::factory()->create();

    $user = User::factory()->create();
    $user->schools()->attach($owned);

    $this->actingAs($user)
        ->getJson('/api/school?school_id='.$unowned->id)
        ->assertForbidden();
});

it('rejects conflicting school identifiers from multiple client inputs', function (): void {
    $first = School::factory()->create();
    $second = School::factory()->create();

    $user = User::factory()->create();
    $user->schools()->attach($first);
    $user->schools()->attach($second);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $first->id)
        ->getJson('/api/school?school_id='.$second->id)
        ->assertBadRequest();
});

it('rejects a malformed school identifier', function (): void {
    $school = School::factory()->create();
    $user = User::factory()->create();
    $user->schools()->attach($school);

    $this->actingAs($user)
        ->withHeader('X-School-Id', 'not-an-integer')
        ->getJson('/api/school')
        ->assertBadRequest();
});

it('treats empty school identifiers as absent without widening access', function (): void {
    $first = School::factory()->create();
    $second = School::factory()->create();

    $user = User::factory()->create();
    $user->schools()->attach($first);
    $user->schools()->attach($second);

    $this->actingAs($user)
        ->withHeader('X-School-Id', '')
        ->getJson('/api/school')
        ->assertBadRequest();

    $this->actingAs($user)
        ->getJson('/api/school?school_id=')
        ->assertBadRequest();

    expect(app(SchoolContext::class)->current())->toBeNull();
});

it('clears the school context after the request completes', function (): void {
    $school = School::factory()->create();
    $user = schoolContextUser($school);

    $this->actingAs($user)->getJson('/api/school')->assertOk();

    expect(app(SchoolContext::class)->current())->toBeNull();
});

it('fails closed when a school-owned query has no authorised context', function (): void {
    SchoolIdentifier::factory()->create();

    expect(fn (): int => SchoolIdentifier::query()->forCurrentSchool()->count())
        ->toThrow(AuthorizationException::class);
});

it('scopes school-owned records explicitly for trusted console paths', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();

    SchoolIdentifier::factory()->for($school)->create();
    SchoolIdentifier::factory()->for($other)->create();

    expect(SchoolIdentifier::query()->forSchool($school)->count())->toBe(1);
});

it('sends tenant-aware cache headers on school context responses', function (): void {
    $school = School::factory()->create();
    $user = schoolContextUser($school);

    $current = $this->actingAs($user)->getJson('/api/school')
        ->assertOk()
        ->assertHeader('Vary', 'X-School-Id');

    $identifiers = $this->actingAs($user)->getJson('/api/school/identifiers')
        ->assertOk()
        ->assertHeader('Vary', 'X-School-Id');

    expect($current->headers->get('Cache-Control'))->toContain('no-store')
        ->and($identifiers->headers->get('Cache-Control'))->toContain('no-store');
});

it('records pivot timestamps when a school membership is created', function (): void {
    $school = School::factory()->create();
    $user = User::factory()->create();

    $user->schools()->attach($school);

    /** @var Pivot $pivot */
    $pivot = $user->schools()->firstOrFail()->pivot;

    expect($pivot->created_at)->not->toBeNull()
        ->and($pivot->updated_at)->not->toBeNull();
});

it('keeps explicit school scoping available on every school-owned model', function (): void {
    foreach ([SchoolIdentifier::class, SchoolStatus::class, SchoolRelationship::class] as $model) {
        expect(class_uses_recursive($model))->toContain(ScopesToCurrentSchool::class);
    }
});
