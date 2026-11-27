<?php

declare(strict_types=1);

use App\Domains\School\Actions\AddSchoolIdentifier;
use App\Domains\School\Actions\CreateSchool;
use App\Domains\School\Actions\RecordSchoolRelationship;
use App\Domains\School\Actions\RecordSchoolStatus;
use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolIdentifier;
use App\Domains\School\Models\SchoolRelationship;
use App\Domains\School\Models\SchoolStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a school with evidence-backed identity fields', function (): void {
    $school = app(CreateSchool::class)->execute([
        'name' => '  Example High School  ',
        'type' => 'government',
        'ward' => 'Central',
        'district' => 'Harare',
        'province' => 'Harare Province',
    ]);

    expect($school->exists)->toBeTrue()
        ->and($school->name)->toBe('Example High School')
        ->and($school->type)->toBe('government');

    $this->assertDatabaseHas('schools', [
        'id' => $school->id,
        'ward' => 'Central',
        'district' => 'Harare',
        'province' => 'Harare Province',
    ]);
});

it('rejects a school without the required identity fields', function (): void {
    expect(fn () => app(CreateSchool::class)->execute([
        'name' => '',
        'district' => 'Harare',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('schools', 0);
});

it('rejects a school type that is not configured', function (): void {
    expect(fn () => app(CreateSchool::class)->execute([
        'name' => 'Example High School',
        'type' => 'unverified-category',
    ]))->toThrow(ValidationException::class);
});

it('stores multiple named identifiers without assuming a code format', function (): void {
    $school = School::factory()->create();
    $action = app(AddSchoolIdentifier::class);

    $emis = $action->execute($school, [
        'type' => 'emis',
        'value' => ' VALUE-A ',
        'source' => 'MoPSE register',
        'verification_status' => 'proposed',
    ]);
    $registration = $action->execute($school, [
        'type' => 'registration_number',
        'value' => 'VALUE-B',
        'source' => 'Education Act registration',
        'verification_status' => 'verified',
    ]);

    expect($emis->value)->toBe('VALUE-A')
        ->and($registration->value)->toBe('VALUE-B')
        ->and($school->identifiers()->count())->toBe(2);
});

it('rejects duplicate identifier values', function (): void {
    $school = School::factory()->create();
    $action = app(AddSchoolIdentifier::class);

    $action->execute($school, ['type' => 'emis', 'value' => 'VALUE-001']);

    expect(fn () => $action->execute($school, ['type' => 'emis', 'value' => 'value-001']))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('school_identifiers', 1);
});

it('uses a configured identifier pattern when one is supplied', function (): void {
    config()->set('schools.identifier_types.emis.pattern', '/^[A-Z]{2}-\d{4}$/');
    $school = School::factory()->create();
    $action = app(AddSchoolIdentifier::class);

    expect(fn () => $action->execute($school, ['type' => 'emis', 'value' => 'NOT-VALID']))
        ->toThrow(ValidationException::class);

    $identifier = $action->execute($school, ['type' => 'emis', 'value' => 'AB-1234']);

    expect($identifier->value)->toBe('AB-1234');
});

it('keeps status history and resolves the status effective at a given date', function (): void {
    $school = School::factory()->create();
    $action = app(RecordSchoolStatus::class);

    $action->execute($school, [
        'status' => 'provisional',
        'valid_from' => '2025-01-01',
        'valid_to' => '2025-12-31',
    ]);
    $action->execute($school, [
        'status' => 'registered',
        'valid_from' => '2026-01-01',
        'source' => 'MoPSE register',
    ]);

    expect($school->statuses()->count())->toBe(2)
        ->and($school->currentStatus(Carbon::parse('2025-06-01'))?->status)->toBe('provisional')
        ->and($school->currentStatus(Carbon::parse('2026-06-01'))?->status)->toBe('registered')
        ->and($school->currentStatus(Carbon::parse('2024-06-01')))->toBeNull();
});

it('rejects overlapping periods for the same school status', function (): void {
    $school = School::factory()->create();
    $action = app(RecordSchoolStatus::class);

    $action->execute($school, ['status' => 'registered', 'valid_from' => '2026-01-01']);

    expect(fn () => $action->execute($school, [
        'status' => 'registered',
        'valid_from' => '2026-06-01',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('school_statuses', 1);
});

it('rejects overlapping periods across different statuses for the same school', function (): void {
    $school = School::factory()->create();
    $action = app(RecordSchoolStatus::class);

    $action->execute($school, ['status' => 'provisional', 'valid_from' => '2026-01-01']);

    expect(fn () => $action->execute($school, [
        'status' => 'registered',
        'valid_from' => '2026-06-01',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('school_statuses', 1);
});

it('records a satellite relationship and rejects a relationship that creates a cycle', function (): void {
    $motherSchool = School::factory()->create();
    $satelliteSchool = School::factory()->create();
    $action = app(RecordSchoolRelationship::class);

    $relationship = $action->execute($satelliteSchool, [
        'related_school_id' => $motherSchool->id,
        'relationship_type' => 'satellite_of',
        'valid_from' => '2026-01-01',
        'evidence_reference' => 'MoPSE register',
    ]);

    expect($relationship->school->is($satelliteSchool))->toBeTrue()
        ->and($relationship->relatedSchool->is($motherSchool))->toBeTrue();

    expect(fn () => $action->execute($motherSchool, [
        'related_school_id' => $satelliteSchool->id,
        'relationship_type' => 'satellite_of',
        'valid_from' => '2026-01-01',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('school_relationships', 1);
});

it('rejects a school relationship to itself', function (): void {
    $school = School::factory()->create();

    expect(fn () => app(RecordSchoolRelationship::class)->execute($school, [
        'related_school_id' => $school->id,
        'relationship_type' => 'satellite_of',
        'valid_from' => '2026-01-01',
    ]))->toThrow(ValidationException::class);
});

it('cascades deletion to identity and relationship records', function (): void {
    $school = School::factory()->create();
    SchoolIdentifier::factory()->for($school)->create();
    SchoolStatus::factory()->for($school)->create();
    SchoolRelationship::factory()->for($school)->create();

    $school->delete();

    $this->assertDatabaseCount('schools', 1)
        ->assertDatabaseCount('school_identifiers', 0)
        ->assertDatabaseCount('school_statuses', 0)
        ->assertDatabaseCount('school_relationships', 0);
});
