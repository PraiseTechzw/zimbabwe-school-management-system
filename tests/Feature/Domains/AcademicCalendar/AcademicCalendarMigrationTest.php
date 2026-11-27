<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates academic calendar tables with ownership, dates, status and source columns', function (): void {
    expect(Schema::hasTable('academic_years'))->toBeTrue()
        ->and(Schema::hasColumns('academic_years', [
            'id', 'school_id', 'name', 'start_date', 'end_date', 'status',
            'source', 'source_reference', 'source_published_at', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasTable('academic_periods'))->toBeTrue()
        ->and(Schema::hasColumns('academic_periods', [
            'id', 'school_id', 'academic_year_id', 'name', 'type', 'sequence',
            'start_date', 'end_date', 'status', 'counting_basis',
            'source', 'source_reference', 'source_published_at', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('enforces unique year names and period names and sequences at the database layer', function (): void {
    $school = School::factory()->create();
    $year = AcademicYear::factory()->for($school)->create([
        'name' => 'Schema year',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    expect(fn () => AcademicYear::query()->create([
        'school_id' => $school->id,
        'name' => 'Schema year',
        'start_date' => '2028-01-01',
        'end_date' => '2028-12-31',
        'status' => 'draft',
    ]))->toThrow(QueryException::class);

    AcademicPeriod::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'name' => 'Schema block',
        'type' => 'term',
        'sequence' => 1,
        'start_date' => '2027-01-01',
        'end_date' => '2027-03-31',
        'status' => 'draft',
    ]);

    expect(fn () => AcademicPeriod::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'name' => 'Schema block',
        'type' => 'term',
        'sequence' => 2,
        'start_date' => '2027-04-01',
        'end_date' => '2027-06-30',
        'status' => 'draft',
    ]))->toThrow(QueryException::class);

    expect(fn () => AcademicPeriod::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'name' => 'Another block',
        'type' => 'term',
        'sequence' => 1,
        'start_date' => '2027-04-01',
        'end_date' => '2027-06-30',
        'status' => 'draft',
    ]))->toThrow(QueryException::class);
});

it('cascades academic calendar records when their school is deleted', function (): void {
    $school = School::factory()->create();
    $year = AcademicYear::factory()->for($school)->create();
    AcademicPeriod::factory()->inYear($year)->create();

    $school->delete();

    $this->assertDatabaseCount('academic_years', 0)
        ->assertDatabaseCount('academic_periods', 0);
});
