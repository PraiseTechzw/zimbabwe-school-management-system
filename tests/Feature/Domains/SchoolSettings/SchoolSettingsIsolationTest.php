<?php

declare(strict_types=1);

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use App\Domains\SchoolSettings\Models\SchoolSettingRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function settingsHttpUser(School ...$schools): User
{
    $user = User::factory()->create();

    foreach ($schools as $school) {
        $user->schools()->attach($school);
        grantSchoolRole($user, $school, 'school_editor');
    }

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function settingsHttpPayload(array $overrides = []): array
{
    return array_merge([
        'locale' => 'en',
        'timezone' => 'Africa/Harare',
        'source' => 'School configuration register',
        'effective_from' => '2031-03-01',
    ], $overrides);
}

it('requires authentication and school access for settings endpoints', function (string $endpoint, string $method): void {
    $this->json($method, $endpoint)->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->json($method, $endpoint)
        ->assertForbidden();
})->with([
    'read settings' => ['/api/school-settings', 'GET'],
    'read revisions' => ['/api/school-settings/revisions', 'GET'],
    'update settings' => ['/api/school-settings', 'PUT'],
]);

it('returns defaults without writing tenant data and sends isolation headers', function (): void {
    $school = School::factory()->create();
    $user = settingsHttpUser($school);

    $response = $this->actingAs($user)
        ->getJson('/api/school-settings')
        ->assertOk()
        ->assertJsonPath('school_id', $school->id)
        ->assertJsonPath('settings.locale', 'en')
        ->assertJsonPath('settings.timezone', 'UTC')
        ->assertJsonPath('revision.version', 1)
        ->assertJsonPath('revision.source', null)
        ->assertJsonPath('revision.effective_from', null)
        ->assertHeader('Vary', 'X-School-Id, school_id');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->assertDatabaseCount('school_settings', 0);
});

it('updates only the resolved school settings through the API', function (): void {
    $school = School::factory()->create();
    $user = settingsHttpUser($school);

    $this->actingAs($user)
        ->putJson('/api/school-settings', settingsHttpPayload())
        ->assertOk()
        ->assertJsonPath('school_id', $school->id)
        ->assertJsonPath('settings.timezone', 'Africa/Harare')
        ->assertJsonPath('revision.version', 1)
        ->assertJsonPath('revision.source', 'School configuration register')
        ->assertJsonPath('revision.effective_from', '2031-03-01');

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'timezone' => 'Africa/Harare',
        'version' => 1,
    ]);
});

it('rejects client-controlled tenant and unsupported setting fields', function (array $payload, string $field): void {
    $school = School::factory()->create();
    $user = settingsHttpUser($school);

    if (array_key_exists('school_id', $payload)) {
        $payload['school_id'] = $school->id;
    }

    $this->actingAs($user)
        ->putJson('/api/school-settings', settingsHttpPayload($payload))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('school_settings', 0);
})->with([
    'client school id' => [['school_id' => 999], 'school_id'],
    'unsupported locale' => [['locale' => 'fr'], 'locale'],
    'invalid timezone' => [['timezone' => 'Not/AZone'], 'timezone'],
    'blank source' => [['source' => '   '], 'source'],
]);

it('rejects a client school id on read endpoints', function (string $endpoint): void {
    $school = School::factory()->create();
    $user = settingsHttpUser($school);

    $this->actingAs($user)
        ->getJson($endpoint.'?school_id='.$school->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors('school_id');
})->with([
    '/api/school-settings',
    '/api/school-settings/revisions',
]);

it('does not read or change another school settings or revisions', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = settingsHttpUser($school);

    $otherSettings = SchoolSetting::factory()->forSchool($otherSchool)->create([
        'locale' => 'en',
        'timezone' => 'America/New_York',
        'source' => 'Other school register',
        'effective_from' => '2031-01-01',
    ]);
    SchoolSettingRevision::factory()->forSetting($otherSettings)->create([
        'version' => 1,
        'values' => ['locale' => 'en', 'timezone' => 'Australia/Sydney'],
        'source' => 'Other school prior entry',
        'effective_from' => '2030-01-01',
    ]);

    $this->actingAs($user)
        ->getJson('/api/school-settings')
        ->assertOk()
        ->assertJsonPath('school_id', $school->id)
        ->assertJsonPath('settings.timezone', 'UTC');

    $this->actingAs($user)
        ->getJson('/api/school-settings/revisions')
        ->assertOk()
        ->assertJsonCount(0, 'revisions');

    $this->actingAs($user)
        ->putJson('/api/school-settings', settingsHttpPayload())
        ->assertOk()
        ->assertJsonPath('school_id', $school->id);

    expect($otherSettings->fresh()->timezone)->toBe('America/New_York')
        ->and($otherSettings->fresh()->version)->toBe(1);

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $otherSchool->id,
        'timezone' => 'America/New_York',
    ]);
});

it('uses an authorised header selection as the settings school without trusting the value alone', function (): void {
    $first = School::factory()->create();
    $second = School::factory()->create();
    $user = settingsHttpUser($first, $second);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $second->id)
        ->putJson('/api/school-settings', settingsHttpPayload(['timezone' => 'Asia/Tokyo']))
        ->assertOk()
        ->assertJsonPath('school_id', $second->id);

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $second->id,
        'timezone' => 'Asia/Tokyo',
    ])->assertDatabaseCount('school_settings', 1);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $second->id)
        ->getJson('/api/school-settings/revisions')
        ->assertOk()
        ->assertJsonPath('school_id', $second->id);
});

it('denies a school selection outside the user memberships', function (): void {
    $owned = School::factory()->create();
    $unowned = School::factory()->create();
    $user = settingsHttpUser($owned);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $unowned->id)
        ->getJson('/api/school-settings')
        ->assertForbidden();
});
