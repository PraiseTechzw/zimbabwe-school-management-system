<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
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
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\School\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureLevelData(array $overrides = []): array
{
    return array_merge([
        'name' => '  Early Years  ',
        'code' => ' ecd ',
        'display_order' => 1,
        'is_active' => true,
        'configuration' => ['tracking' => ['enabled' => true]],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureGradeData(EducationalLevel $level, array $overrides = []): array
{
    return array_merge([
        'educational_level_id' => (int) $level->id,
        'name' => '  First Year  ',
        'code' => ' g1 ',
        'display_order' => 1,
        'is_active' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureClassData(GradeLevel $grade, array $overrides = []): array
{
    return array_merge([
        'grade_level_id' => (int) $grade->id,
        'name' => '  First Year A  ',
        'code' => ' g1a ',
        'display_order' => 1,
        'is_active' => true,
        'configuration' => ['room' => 'North block'],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureStreamData(ClassDefinition $class, array $overrides = []): array
{
    return array_merge([
        'class_definition_id' => (int) $class->id,
        'name' => '  Stream A  ',
        'code' => ' a ',
        'display_order' => 1,
        'is_active' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureOfferingData(ClassDefinition $class, AcademicYear $year, array $overrides = []): array
{
    return array_merge([
        'class_definition_id' => (int) $class->id,
        'academic_year_id' => (int) $year->id,
        'name' => null,
        'is_active' => true,
        'configuration' => ['note' => 'structural offering'],
    ], $overrides);
}

function structureYearFor(School $school, string $name = 'Cycle 2031'): AcademicYear
{
    return app(CreateAcademicYear::class)->execute($school, [
        'name' => $name,
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
    ]);
}

it('creates a school owned educational level with configurable metadata', function (): void {
    $school = School::factory()->create();

    $level = app(CreateEducationalLevel::class)->execute($school, structureLevelData());

    expect($level->exists)->toBeTrue()
        ->and($level->name)->toBe('Early Years')
        ->and($level->code)->toBe('ecd')
        ->and($level->display_order)->toBe(1)
        ->and($level->is_active)->toBeTrue()
        ->and($level->configuration)->toBe(['tracking' => ['enabled' => true]])
        ->and((int) $level->school_id)->toBe((int) $school->id);

    $this->assertDatabaseHas('educational_levels', [
        'id' => $level->id,
        'school_id' => $school->id,
        'name' => 'Early Years',
        'code' => 'ecd',
        'display_order' => 1,
    ]);
});

it('rejects invalid educational level input', function (array $overrides): void {
    $school = School::factory()->create();

    expect(fn () => app(CreateEducationalLevel::class)->execute($school, structureLevelData($overrides)))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('educational_levels', 0);
})->with([
    'blank name' => [['name' => '   ']],
    'oversized name' => [['name' => str_repeat('a', 256)]],
    'blank code' => [['code' => '   ']],
    'zero order' => [['display_order' => 0]],
    'negative order' => [['display_order' => -1]],
    'scalar configuration' => [['configuration' => 'not-an-array']],
    'non boolean active' => [['is_active' => 'maybe']],
]);

it('enforces level uniqueness within a school but permits the same structure elsewhere', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $action = app(CreateEducationalLevel::class);

    $first = $action->execute($school, structureLevelData(['name' => 'Foundation', 'code' => 'F', 'display_order' => 1]));
    $elsewhere = $action->execute($other, structureLevelData(['name' => 'Foundation', 'code' => 'F', 'display_order' => 1]));

    expect($elsewhere->name)->toBe($first->name);

    expect(fn () => $action->execute($school, structureLevelData(['name' => 'foundation', 'code' => 'F2', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureLevelData(['name' => 'Foundation B', 'code' => 'f', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureLevelData(['name' => 'Foundation B', 'code' => 'F2', 'display_order' => 1])))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('educational_levels', 2);
});

it('updates an educational level and keeps its uniqueness checks scoped to itself', function (): void {
    $school = School::factory()->create();
    $action = app(CreateEducationalLevel::class);
    $level = $action->execute($school, structureLevelData(['name' => 'First', 'code' => 'A', 'display_order' => 2]));
    $other = $action->execute($school, structureLevelData(['name' => 'Second', 'code' => 'B', 'display_order' => 3]));

    $updated = app(UpdateEducationalLevel::class)->execute($school, $level, [
        'name' => 'Renamed',
        'code' => 'C',
        'display_order' => 4,
        'is_active' => false,
        'configuration' => ['review' => '2027'],
    ]);

    expect($updated->is($level))->toBeTrue()
        ->and($updated->name)->toBe('Renamed')
        ->and($updated->code)->toBe('C')
        ->and($updated->display_order)->toBe(4)
        ->and($updated->is_active)->toBeFalse()
        ->and($updated->configuration)->toBe(['review' => '2027']);

    expect(fn () => app(UpdateEducationalLevel::class)->execute($school, $level, ['name' => 'second']))
        ->toThrow(ValidationException::class);
    expect(fn () => app(UpdateEducationalLevel::class)->execute($school, $level, ['display_order' => 3]))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseHas('educational_levels', ['id' => $other->id, 'name' => 'Second', 'display_order' => 3]);
});

it('keeps the stored active state when a domain update supplies null', function (): void {
    $school = School::factory()->create();
    $create = app(CreateEducationalLevel::class);
    $update = app(UpdateEducationalLevel::class);
    $active = $create->execute($school, structureLevelData(['name' => 'Active', 'code' => 'ACT', 'display_order' => 1]));
    $inactive = $create->execute($school, structureLevelData([
        'name' => 'Inactive',
        'code' => 'INA',
        'display_order' => 2,
        'is_active' => false,
    ]));

    expect($update->execute($school, $active, ['is_active' => null])->is_active)->toBeTrue()
        ->and($update->execute($school, $inactive, ['is_active' => null])->is_active)->toBeFalse();
});

it('creates grade levels only under an owned active educational level', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $action = app(CreateGradeLevel::class);

    $grade = $action->execute($school, structureGradeData($level));

    expect($grade->exists)->toBeTrue()
        ->and($grade->name)->toBe('First Year')
        ->and($grade->code)->toBe('g1')
        ->and((int) $grade->school_id)->toBe((int) $school->id)
        ->and((int) $grade->educational_level_id)->toBe((int) $level->id)
        ->and($level->gradeLevels()->pluck('id')->all())->toBe([$grade->id]);
});

it('rejects grade levels attached to foreign or inactive levels', function (): void {
    $school = School::factory()->create();
    $foreign = EducationalLevel::factory()->create();
    $inactive = EducationalLevel::factory()->for($school)->create(['is_active' => false]);
    $action = app(CreateGradeLevel::class);

    expect(fn () => $action->execute($school, structureGradeData($foreign)))
        ->toThrow(ValidationException::class, 'The selected educational level is not available.');
    expect(fn () => $action->execute($school, structureGradeData($inactive)))
        ->toThrow(ValidationException::class, 'The selected educational level is inactive.');

    config()->set('academic-structure.require_active_parent', false);

    expect(fn () => $action->execute($school, structureGradeData($inactive)))
        ->not->toThrow(ValidationException::class);
});

it('enforces grade name and code uniqueness per school and ordering per level', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $otherLevel = EducationalLevel::factory()->for($school)->create(['display_order' => 2]);
    $action = app(CreateGradeLevel::class);

    $first = $action->execute($school, structureGradeData($level, ['name' => 'One', 'code' => 'O', 'display_order' => 1]));
    $action->execute($school, structureGradeData($otherLevel, ['name' => 'Two', 'code' => 'T', 'display_order' => 1]));

    expect(fn () => $action->execute($school, structureGradeData($level, ['name' => ' one ', 'code' => 'X', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureGradeData($level, ['name' => 'Three', 'code' => 'o', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureGradeData($level, ['name' => 'Three', 'code' => 'X', 'display_order' => 1])))
        ->toThrow(ValidationException::class);

    $updated = app(UpdateGradeLevel::class)->execute($school, $first, [
        'name' => 'Renamed grade',
        'display_order' => 5,
    ]);

    expect($updated->name)->toBe('Renamed grade')
        ->and($updated->display_order)->toBe(5);
});

it('rejects moving a grade level to a foreign educational level', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $foreign = EducationalLevel::factory()->create();

    expect(fn () => app(UpdateGradeLevel::class)->execute($school, $grade, [
        'educational_level_id' => (int) $foreign->id,
    ]))->toThrow(ValidationException::class, 'The selected educational level is not available.');

    $this->assertDatabaseHas('grade_levels', [
        'id' => $grade->id,
        'educational_level_id' => $level->id,
    ]);
});

it('creates structural class definitions under an owned grade without enrolment', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $action = app(CreateClassDefinition::class);

    $class = $action->execute($school, structureClassData($grade));

    expect($class->name)->toBe('First Year A')
        ->and($class->configuration)->toBe(['room' => 'North block'])
        ->and((int) $class->school_id)->toBe((int) $school->id)
        ->and((int) $class->grade_level_id)->toBe((int) $grade->id)
        ->and($class->streams()->count())->toBe(0)
        ->and($grade->classes()->pluck('id')->all())->toBe([$class->id]);
});

it('rejects class definitions attached to foreign or inactive grades and duplicates in one grade', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $otherGrade = GradeLevel::factory()->inLevel($level)->create(['display_order' => 2]);
    $foreign = GradeLevel::factory()->create();
    $inactive = GradeLevel::factory()->inLevel($level)->create(['is_active' => false, 'display_order' => 3]);
    $action = app(CreateClassDefinition::class);

    $action->execute($school, structureClassData($grade, ['name' => 'Alpha', 'code' => 'A', 'display_order' => 1]));

    expect(fn () => $action->execute($school, structureClassData($foreign)))
        ->toThrow(ValidationException::class, 'The selected grade level is not available.');
    expect(fn () => $action->execute($school, structureClassData($inactive)))
        ->toThrow(ValidationException::class, 'The selected grade level is inactive.');
    expect(fn () => $action->execute($school, structureClassData($grade, ['name' => 'alpha', 'code' => 'B', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureClassData($grade, ['name' => 'Beta', 'code' => 'a', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureClassData($grade, ['name' => 'Beta', 'code' => 'B', 'display_order' => 1])))
        ->toThrow(ValidationException::class);

    $action->execute($school, structureClassData($otherGrade, ['name' => 'Alpha', 'code' => 'A', 'display_order' => 1]));

    app(UpdateClassDefinition::class)->execute(
        $school,
        ClassDefinition::query()->where('grade_level_id', $grade->id)->firstOrFail(),
        ['name' => 'Renamed', 'display_order' => 7],
    );

    $this->assertDatabaseHas('class_definitions', ['grade_level_id' => $grade->id, 'name' => 'Renamed', 'display_order' => 7]);
});

it('treats streams as optional and validates them within one class', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $otherClass = ClassDefinition::factory()->inGrade($grade)->create(['display_order' => 2, 'name' => 'Other class']);
    $foreign = ClassDefinition::factory()->create();
    $inactive = ClassDefinition::factory()->inGrade($grade)->create(['is_active' => false, 'display_order' => 3, 'name' => 'Inactive class']);
    $action = app(CreateStream::class);

    expect($class->streams()->count())->toBe(0);

    $stream = $action->execute($school, structureStreamData($class));
    $action->execute($school, structureStreamData($otherClass));

    expect($stream->name)->toBe('Stream A')
        ->and((int) $stream->school_id)->toBe((int) $school->id)
        ->and($class->streams()->pluck('id')->all())->toBe([$stream->id]);

    expect(fn () => $action->execute($school, structureStreamData($foreign)))
        ->toThrow(ValidationException::class, 'The selected class definition is not available.');
    expect(fn () => $action->execute($school, structureStreamData($inactive)))
        ->toThrow(ValidationException::class, 'The selected class definition is inactive.');
    expect(fn () => $action->execute($school, structureStreamData($class, ['name' => 'stream a', 'code' => 'B', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureStreamData($class, ['name' => 'Stream B', 'code' => 'a', 'display_order' => 2])))
        ->toThrow(ValidationException::class);
    expect(fn () => $action->execute($school, structureStreamData($class, ['name' => 'Stream B', 'code' => 'B', 'display_order' => 1])))
        ->toThrow(ValidationException::class);

    $updated = app(UpdateStream::class)->execute($school, $stream, ['name' => 'Group One', 'display_order' => 4]);

    expect($updated->name)->toBe('Group One')
        ->and($updated->display_order)->toBe(4);
});

it('offers structural classes in an owned academic year', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $year = structureYearFor($school);

    $offering = app(CreateClassOffering::class)->execute($school, structureOfferingData($class, $year));

    expect($offering->exists)->toBeTrue()
        ->and($offering->name)->toBeNull()
        ->and((int) $offering->school_id)->toBe((int) $school->id)
        ->and((int) $offering->class_definition_id)->toBe((int) $class->id)
        ->and((int) $offering->academic_year_id)->toBe((int) $year->id)
        ->and($class->offerings()->pluck('id')->all())->toBe([$offering->id]);
});

it('rejects offerings for foreign associations and prohibits duplicate class offerings in one year', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $secondClass = ClassDefinition::factory()->inGrade($grade)->create(['display_order' => 2, 'name' => 'Second class']);
    $year = structureYearFor($school);
    $foreignYear = structureYearFor($other, 'Foreign cycle');
    $foreignClass = ClassDefinition::factory()->create();
    $action = app(CreateClassOffering::class);

    $action->execute($school, structureOfferingData($class, $year, ['name' => 'Named offering']));

    expect(fn () => $action->execute($school, structureOfferingData($class, $year)))
        ->toThrow(ValidationException::class, 'This class is already offered in the selected academic year.');
    expect(fn () => $action->execute($school, structureOfferingData($secondClass, $year, ['name' => 'named offering'])))
        ->toThrow(ValidationException::class, 'An offering with this name already exists in the selected academic year.');
    expect(fn () => $action->execute($school, structureOfferingData($foreignClass, $year)))
        ->toThrow(ValidationException::class, 'The selected class definition is not available.');
    expect(fn () => $action->execute($school, structureOfferingData($class, $foreignYear)))
        ->toThrow(ValidationException::class, 'The selected academic year is not available.');

    $this->assertDatabaseCount('class_offerings', 1);
});

it('allows offerings of inactive classes only when explicitly configured', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create(['is_active' => false]);
    $year = structureYearFor($school);
    $action = app(CreateClassOffering::class);

    expect(fn () => $action->execute($school, structureOfferingData($class, $year)))
        ->toThrow(ValidationException::class, 'The selected class definition is inactive.');

    config()->set('academic-structure.allow_inactive_class_offering', true);

    expect(fn () => $action->execute($school, structureOfferingData($class, $year)))
        ->not->toThrow(ValidationException::class);
});

it('updates offerings while preserving school ownership and uniqueness', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $first = ClassDefinition::factory()->inGrade($grade)->create(['name' => 'First']);
    $second = ClassDefinition::factory()->inGrade($grade)->create(['name' => 'Second', 'display_order' => 2]);
    $year = structureYearFor($school);
    $action = app(CreateClassOffering::class);
    $offering = $action->execute($school, structureOfferingData($first, $year));
    $action->execute($school, structureOfferingData($second, $year, ['name' => 'Taken']));

    expect(fn () => app(UpdateClassOffering::class)->execute($school, $offering, [
        'class_definition_id' => (int) $second->id,
    ]))->toThrow(ValidationException::class);

    $updated = app(UpdateClassOffering::class)->execute($school, $offering, [
        'name' => 'Renamed offering',
        'is_active' => false,
        'configuration' => ['reviewed' => true],
    ]);

    expect($updated->is($offering))->toBeTrue()
        ->and($updated->name)->toBe('Renamed offering')
        ->and($updated->is_active)->toBeFalse()
        ->and($updated->configuration)->toBe(['reviewed' => true]);
});

it('fails closed when an update target belongs to another school', function () use (&$foreignGrade): void {
    $school = School::factory()->create();
    $foreignSchool = School::factory()->create();
    $level = EducationalLevel::factory()->for($foreignSchool)->create();
    $foreignGrade = GradeLevel::factory()->inLevel($level)->create();

    expect(fn () => app(UpdateGradeLevel::class)->execute($school, $foreignGrade, ['name' => 'Changed']))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('grade_levels', ['id' => $foreignGrade->id, 'name' => $foreignGrade->name]);
});

it('rolls back a structure write when the surrounding transaction fails', function (): void {
    $school = School::factory()->create();

    try {
        DB::transaction(function () use ($school): void {
            app(CreateEducationalLevel::class)->execute($school, structureLevelData(['name' => 'Temporary']));
            throw new RuntimeException('abort');
        });
    } catch (RuntimeException) {
        // Expected: the transaction must undo all writes.
    }

    $this->assertDatabaseCount('educational_levels', 0);
});
