<?php

declare(strict_types=1);

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates one settings row and append-oriented revisions per school', function (): void {
    expect(Schema::hasTable('school_settings'))->toBeTrue()
        ->and(Schema::hasColumns('school_settings', [
            'id', 'school_id', 'locale', 'timezone', 'source', 'effective_from',
            'version', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasTable('school_setting_revisions'))->toBeTrue()
        ->and(Schema::hasColumns('school_setting_revisions', [
            'id', 'school_id', 'school_setting_id', 'version', 'values',
            'source', 'effective_from', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('enforces a single settings row and unique revision versions per school at the database layer', function (): void {
    $school = School::factory()->create();
    $settings = SchoolSetting::factory()->forSchool($school)->create();

    expect(fn () => SchoolSetting::query()->forceCreate([
        'school_id' => $school->id,
        'locale' => 'en',
        'timezone' => 'UTC',
        'source' => 'Duplicate register entry',
        'effective_from' => '2031-01-01',
        'version' => 1,
    ]))->toThrow(QueryException::class);

    $revisionData = [
        'school_id' => $school->id,
        'school_setting_id' => $settings->id,
        'version' => 1,
        'values' => ['locale' => 'en', 'timezone' => 'UTC'],
        'source' => 'Register entry',
        'effective_from' => '2031-01-01',
    ];

    $settings->revisions()->forceCreate($revisionData);

    expect(fn () => $settings->revisions()->forceCreate($revisionData))->toThrow(QueryException::class);
});

it('cascades settings and revisions when their school is deleted', function (): void {
    $school = School::factory()->create();
    $settings = SchoolSetting::factory()->forSchool($school)->create();
    $settings->revisions()->forceCreate([
        'school_id' => $school->id,
        'school_setting_id' => $settings->id,
        'version' => 1,
        'values' => ['locale' => 'en', 'timezone' => 'UTC'],
        'source' => 'Register entry',
        'effective_from' => '2031-01-01',
    ]);

    $school->delete();

    $this->assertDatabaseCount('school_settings', 0)
        ->assertDatabaseCount('school_setting_revisions', 0);
});
