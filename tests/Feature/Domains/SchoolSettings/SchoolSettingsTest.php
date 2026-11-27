<?php

declare(strict_types=1);

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Actions\ResolveSchoolSettings;
use App\Domains\SchoolSettings\Actions\UpdateSchoolSettings;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('resolves configured defaults without persisting a settings record', function (): void {
    $school = School::factory()->create();

    $settings = app(ResolveSchoolSettings::class)->execute($school);

    expect($settings->locale)->toBe('en')
        ->and($settings->timezone)->toBe('UTC')
        ->and($settings->version)->toBe(1);

    $this->assertDatabaseCount('school_settings', 0);
});

it('rejects invalid or unconfirmed settings values at the domain boundary', function (string $field, mixed $value): void {
    $school = School::factory()->create();

    $data = [
        'source' => 'School configuration register',
        'effective_from' => '2031-01-01',
        $field => $value,
    ];

    expect(fn (): SchoolSetting => app(UpdateSchoolSettings::class)->execute($school, $data))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('school_settings', 0);
})->with([
    'unsupported locale' => ['locale', 'fr'],
    'invalid timezone' => ['timezone', 'Not/AZone'],
    'client supplied school' => ['school_id', 1],
    'blank source' => ['source', '   '],
    'invalid effective date' => ['effective_from', '01-01-2031'],
]);

it('stores the first settings version with provenance', function (): void {
    Carbon::setTestNow('2031-01-01 09:00:00');
    $school = School::factory()->create();

    $settings = app(UpdateSchoolSettings::class)->execute($school, [
        'source' => 'School configuration register',
        'effective_from' => '2031-01-15',
    ]);

    expect($settings->locale)->toBe('en')
        ->and($settings->timezone)->toBe('UTC')
        ->and($settings->source)->toBe('School configuration register')
        ->and($settings->effective_from->toDateString())->toBe('2031-01-15')
        ->and($settings->version)->toBe(1);

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'version' => 1,
        'source' => 'School configuration register',
        'effective_from' => '2031-01-15',
    ])->assertDatabaseCount('school_setting_revisions', 0);

    Carbon::setTestNow();
});

it('retains the previous version, values, source and effective date on each update', function (): void {
    $school = School::factory()->create();

    app(UpdateSchoolSettings::class)->execute($school, [
        'timezone' => 'Africa/Harare',
        'source' => 'Initial register entry',
        'effective_from' => '2031-01-01',
    ]);

    $updated = app(UpdateSchoolSettings::class)->execute($school, [
        'locale' => 'en',
        'timezone' => 'UTC',
        'source' => 'Principal approval',
        'effective_from' => '2031-02-01',
    ]);

    expect($updated->version)->toBe(2)
        ->and($updated->values())->toBe(['locale' => 'en', 'timezone' => 'UTC']);

    $this->assertDatabaseHas('school_setting_revisions', [
        'school_id' => $school->id,
        'school_setting_id' => $updated->id,
        'version' => 1,
        'source' => 'Initial register entry',
        'effective_from' => '2031-01-01',
    ]);

    expect(SchoolSetting::query()->findOrFail($updated->id)->revisions()->firstOrFail()->values)
        ->toBe(['locale' => 'en', 'timezone' => 'Africa/Harare']);
});

it('serialises repeated writes through the school row so each version is distinct', function (): void {
    $school = School::factory()->create();

    foreach (range(1, 4) as $version) {
        $settings = app(UpdateSchoolSettings::class)->execute($school, [
            'source' => 'Configuration review '.$version,
            'effective_from' => sprintf('2031-01-%02d', $version),
        ]);

        expect($settings->version)->toBe($version);
    }

    $this->assertDatabaseCount('school_setting_revisions', 3)
        ->assertDatabaseHas('school_setting_revisions', [
            'school_id' => $school->id,
            'version' => 3,
        ]);
});
