<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function learnerSchemaOffering(School $school): ClassOffering
{
    $level = EducationalLevel::factory()->for($school)->create();
    $grade = GradeLevel::factory()->inLevel($level)->create();
    $class = ClassDefinition::factory()->inGrade($grade)->create();
    $year = AcademicYear::factory()->for($school)->create();

    /** @var ClassOffering $offering */
    $offering = ClassOffering::factory()
        ->forClassAndYear($class, $year)
        ->create();

    return $offering;
}

/**
 * @return array<string, mixed>
 */
function learnerSchemaRow(School $school, string $reference, ?int $offeringId = null): array
{
    return [
        'school_id' => $school->id,
        'learner_reference' => $reference,
        'full_name' => 'Schema Learner',
        'date_of_birth' => null,
        'status' => 'registered',
        'class_offering_id' => $offeringId,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

it('creates the learner table with ownership, internal reference and optional placement columns', function (): void {
    expect(Schema::hasTable('learners'))->toBeTrue();

    foreach ([
        'id', 'school_id', 'learner_reference', 'full_name', 'date_of_birth',
        'status', 'class_offering_id', 'created_at', 'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('learners', $column))->toBeTrue();
    }
});

it('creates the expected learner indexes and enforces a global unique reference', function (): void {
    $school = School::factory()->create();
    $names = collect(DB::select("PRAGMA index_list('learners')"))->pluck('name')->all();

    foreach ([
        'learners_reference_unique',
        'learners_school_id_id_unique',
        'learners_school_offering_index',
    ] as $index) {
        expect($names)->toContain($index);
    }

    DB::table('learners')->insert(learnerSchemaRow($school, 'LRN-SCHEMA000000000000000000001'));

    expect(fn (): int => DB::table('learners')->insertGetId(
        learnerSchemaRow($school, 'LRN-SCHEMA000000000000000000001'),
    ))->toThrow(QueryException::class);

    expect(DB::table('learners')->where('school_id', $school->id)->count())->toBe(1);
});

it('uses a composite foreign key that rejects cross-school placement links', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $foreignOffering = learnerSchemaOffering($other);

    expect(fn (): int => DB::table('learners')->insertGetId(
        learnerSchemaRow($school, 'LRN-SCHEMA000000000000000000002', (int) $foreignOffering->id),
    ))->toThrow(QueryException::class);

    expect(DB::table('learners')->where('school_id', $school->id)->count())->toBe(0);
});

it('rejects deletion of an offering that is still referenced by a learner placement', function (): void {
    $school = School::factory()->create();
    $offering = learnerSchemaOffering($school);

    $learner = Learner::factory()->for($school)->create([
        'class_offering_id' => $offering->id,
    ]);

    expect(fn (): bool => $offering->delete())->toThrow(QueryException::class);

    expect($learner->fresh()->exists)->toBeTrue()
        ->and((int) $learner->fresh()->class_offering_id)->toBe((int) $offering->id)
        ->and(DB::table('class_offerings')->where('id', $offering->id)->exists())->toBeTrue();
});

it('cascades learner deletion when the owning school is deleted', function (): void {
    $school = School::factory()->create();
    $offering = learnerSchemaOffering($school);
    $learner = Learner::factory()->for($school)->create([
        'class_offering_id' => $offering->id,
    ]);

    $school->delete();

    expect(DB::table('learners')->where('id', $learner->id)->exists())->toBeFalse()
        ->and(DB::table('class_offerings')->where('id', $offering->id)->exists())->toBeFalse();
});
