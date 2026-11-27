<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\School\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function structureSchemaYear(School $school, string $name): int
{
    return (int) app(CreateAcademicYear::class)->execute($school, [
        'name' => $name,
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
    ])->id;
}

it('creates all academic structure tables with school ownership columns', function (): void {
    foreach ([
        'educational_levels',
        'grade_levels',
        'class_definitions',
        'streams',
        'class_offerings',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue()
            ->and(Schema::hasColumn($table, 'school_id'))->toBeTrue();
    }

    expect(Schema::hasColumn('educational_levels', 'configuration'))->toBeTrue()
        ->and(Schema::hasColumn('class_definitions', 'configuration'))->toBeTrue()
        ->and(Schema::hasColumn('class_offerings', 'configuration'))->toBeTrue()
        ->and(Schema::hasColumn('class_offerings', 'academic_year_id'))->toBeTrue();
});

it('uses composite foreign keys that reject cross-school parent associations at the database layer', function (string $table): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $timestamp = now()->format('Y-m-d H:i:s');

    $foreignLevelId = DB::table('educational_levels')->insertGetId([
        'school_id' => $other->id,
        'name' => 'Foreign level',
        'code' => null,
        'display_order' => 990001,
        'is_active' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $foreignGradeId = DB::table('grade_levels')->insertGetId([
        'school_id' => $other->id,
        'educational_level_id' => $foreignLevelId,
        'name' => 'Foreign grade',
        'code' => null,
        'display_order' => 990001,
        'is_active' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $foreignClassId = DB::table('class_definitions')->insertGetId([
        'school_id' => $other->id,
        'grade_level_id' => $foreignGradeId,
        'name' => 'Foreign class',
        'code' => null,
        'display_order' => 990001,
        'is_active' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $payload = match ($table) {
        'grade_levels' => [
            'school_id' => $school->id,
            'educational_level_id' => $foreignLevelId,
            'name' => 'Orphan grade',
            'code' => null,
            'display_order' => 990002,
            'is_active' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        'class_definitions' => [
            'school_id' => $school->id,
            'grade_level_id' => $foreignGradeId,
            'name' => 'Orphan class',
            'code' => null,
            'display_order' => 990002,
            'is_active' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        'streams' => [
            'school_id' => $school->id,
            'class_definition_id' => $foreignClassId,
            'name' => 'Orphan stream',
            'code' => null,
            'display_order' => 990002,
            'is_active' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    };

    expect(fn (): int => DB::table($table)->insertGetId($payload))->toThrow(QueryException::class);

    expect(DB::table($table)->where('school_id', $school->id)->count())->toBe(0);
})->with([
    'grade -> level' => ['grade_levels'],
    'class -> grade' => ['class_definitions'],
    'stream -> class' => ['streams'],
]);

it('enforces per-school ordering and naming uniqueness in the database', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create(['display_order' => 1, 'name' => 'First']);
    $timestamp = now()->format('Y-m-d H:i:s');

    $duplicateName = fn (): int => DB::table('educational_levels')->insertGetId([
        'school_id' => $school->id,
        'name' => 'First',
        'code' => null,
        'display_order' => 2,
        'is_active' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    $duplicateOrder = fn (): int => DB::table('educational_levels')->insertGetId([
        'school_id' => $school->id,
        'name' => 'Second',
        'code' => null,
        'display_order' => 1,
        'is_active' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    expect($duplicateName)->toThrow(QueryException::class)
        ->and($duplicateOrder)->toThrow(QueryException::class);

    expect(EducationalLevel::query()->forSchool($school)->count())->toBe(1)
        ->and($level->name)->toBe('First');
});

it('prevents duplicate offerings and cross-school academic year links in the database', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $secondClass = ClassDefinition::factory()->inGrade($grade)->create([
        'name' => 'Second class',
        'display_order' => 2,
    ]);
    $yearId = structureSchemaYear($school, 'Owned year');
    $foreignYearId = structureSchemaYear($other, 'Foreign year');
    $timestamp = now()->format('Y-m-d H:i:s');

    $insert = fn (int $year, int $classId, ?string $name): int => DB::table('class_offerings')->insertGetId([
        'school_id' => $school->id,
        'class_definition_id' => $classId,
        'academic_year_id' => $year,
        'name' => $name,
        'is_active' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    expect(fn () => $insert($yearId, $class->id, null))->not->toThrow(QueryException::class);
    expect(fn () => $insert($yearId, $class->id, null))->toThrow(QueryException::class);
    expect(fn () => $insert($yearId, $secondClass->id, 'Named'))->not->toThrow(QueryException::class);
    expect(fn () => $insert($yearId, $secondClass->id, 'Named'))->toThrow(QueryException::class);
    expect(fn () => $insert($foreignYearId, $secondClass->id, 'Foreign link'))->toThrow(QueryException::class);

    expect(DB::table('class_offerings')->where('school_id', $school->id)->count())->toBe(2);
});

it('cascades structure deletion from a school and from parents', function (): void {
    $school = School::factory()->create();
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $yearId = structureSchemaYear($school, 'Cascade year');

    DB::table('class_offerings')->insert([
        'school_id' => $school->id,
        'class_definition_id' => $class->id,
        'academic_year_id' => $yearId,
        'name' => null,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $level->delete();

    expect(DB::table('grade_levels')->where('id', $grade->id)->exists())->toBeFalse()
        ->and(DB::table('class_definitions')->where('id', $class->id)->exists())->toBeFalse()
        ->and(DB::table('class_offerings')->where('academic_year_id', $yearId)->exists())->toBeFalse();
});
