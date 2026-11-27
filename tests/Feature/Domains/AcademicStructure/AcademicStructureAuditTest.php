<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Actions\CreateClassDefinition;
use App\Domains\AcademicStructure\Actions\CreateClassOffering;
use App\Domains\AcademicStructure\Actions\CreateEducationalLevel;
use App\Domains\AcademicStructure\Actions\CreateGradeLevel;
use App\Domains\AcademicStructure\Actions\CreateStream;
use App\Domains\AcademicStructure\Actions\UpdateClassDefinition;
use App\Domains\AcademicStructure\Actions\UpdateClassOffering;
use App\Domains\AcademicStructure\Actions\UpdateEducationalLevel;
use App\Domains\AcademicStructure\Actions\UpdateGradeLevel;
use App\Domains\AcademicStructure\Actions\UpdateStream;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('records deliberate audit events for structure configuration writes', function (): void {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();

    $year = AcademicYear::factory()->for($school)->create([
        'name' => '2031',
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
    ]);

    $level = app(CreateEducationalLevel::class)->execute($school, [
        'name' => 'Foundation',
        'code' => 'FOU',
        'display_order' => 1,
        'is_active' => true,
        'configuration' => ['custom_label' => 'school defined'],
    ]);

    $grade = app(CreateGradeLevel::class)->execute($school, [
        'educational_level_id' => $level->id,
        'name' => 'Grade One',
        'code' => 'G1',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $class = app(CreateClassDefinition::class)->execute($school, [
        'grade_level_id' => $grade->id,
        'name' => 'Class A',
        'code' => 'A',
        'display_order' => 1,
        'is_active' => true,
        'configuration' => ['custom_label' => 'school defined'],
    ]);

    $stream = app(CreateStream::class)->execute($school, [
        'class_definition_id' => $class->id,
        'name' => 'Stream A',
        'code' => 'SA',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $offering = app(CreateClassOffering::class)->execute($school, [
        'class_definition_id' => $class->id,
        'academic_year_id' => $year->id,
        'name' => 'Offering A',
        'is_active' => true,
        'configuration' => ['custom_label' => 'school defined'],
    ]);

    app(UpdateEducationalLevel::class)->execute($school, $level, ['name' => 'Foundation updated']);
    app(UpdateGradeLevel::class)->execute($school, $grade, ['name' => 'Grade One updated']);
    app(UpdateClassDefinition::class)->execute($school, $class, ['name' => 'Class A updated']);
    app(UpdateStream::class)->execute($school, $stream, ['name' => 'Stream A updated']);
    app(UpdateClassOffering::class)->execute($school, $offering, ['name' => 'Offering A updated']);

    $events = AuditEvent::query()
        ->forSchool($school)
        ->where('category', 'school_configuration')
        ->whereIn('resource_type', [
            'educational_level',
            'grade_level',
            'class_definition',
            'stream',
            'class_offering',
        ])
        ->orderBy('id')
        ->get();

    expect($events->pluck('action')->all())->toBe([
        'educational_level.created',
        'grade_level.created',
        'class_definition.created',
        'stream.created',
        'class_offering.created',
        'educational_level.updated',
        'grade_level.updated',
        'class_definition.updated',
        'stream.updated',
        'class_offering.updated',
    ]);

    $levelCreated = $events->firstWhere('action', 'educational_level.created');

    expect($levelCreated)->not->toBeNull()
        ->and((int) $levelCreated->school_id)->toBe((int) $school->id)
        ->and($levelCreated->source)->toBe('domain')
        ->and($levelCreated->resource_id)->toBe((string) $level->id)
        ->and($levelCreated->changes['after']['name'])->toBe('Foundation')
        ->and($levelCreated->changes['after']['configuration_present'])->toBeTrue()
        ->and($levelCreated->changes['after'])->not->toHaveKey('custom_label');

    $levelUpdated = $events->firstWhere('action', 'educational_level.updated');

    expect($levelUpdated)->not->toBeNull()
        ->and($levelUpdated->changes['before']['name'])->toBe('Foundation')
        ->and($levelUpdated->changes['after']['name'])->toBe('Foundation updated');

    expect(AuditEvent::query()->forSchool($otherSchool)->where('resource_type', 'educational_level')->count())->toBe(0);
});

it('does not record an audit event when a structure write is rejected', function (): void {
    $school = School::factory()->create();

    try {
        app(CreateEducationalLevel::class)->execute($school, [
            'name' => '   ',
            'display_order' => 1,
        ]);
        $this->fail('A blank level name should fail validation.');
    } catch (ValidationException) {
        // Expected: rejected writes must not append audit events.
    }

    expect(EducationalLevel::query()->forSchool($school)->count())->toBe(0)
        ->and(AuditEvent::query()->forSchool($school)->where('category', 'school_configuration')->count())->toBe(0);
});
