<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Actions\CreateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicYear;
use App\Domains\AcademicCalendar\Actions\ValidateAcademicCalendar;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calendarYearData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Learning Year 2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'status' => 'published',
        'source' => 'School calendar register',
        'source_reference' => 'calendar-register/2027',
        'source_published_at' => '2026-11-01',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calendarPeriodData(AcademicYear $year, array $overrides = []): array
{
    return array_merge([
        'academic_year_id' => (int) $year->id,
        'name' => 'Learning Block A',
        'type' => 'term',
        'sequence' => 1,
        'start_date' => '2027-01-10',
        'end_date' => '2027-03-20',
        'status' => 'published',
        'counting_basis' => 'school_days',
        'source' => 'School calendar register',
        'source_reference' => 'calendar-register/2027',
        'source_published_at' => '2026-11-01',
    ], $overrides);
}

it('creates a sourced academic year without assuming universal dates', function (): void {
    $school = School::factory()->create();

    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData([
        'name' => '  Study Period 2031  ',
        'start_date' => '2031-02-08',
        'end_date' => '2031-11-27',
    ]));

    expect($year->exists)->toBeTrue()
        ->and($year->name)->toBe('Study Period 2031')
        ->and($year->status)->toBe('published')
        ->and((int) $year->school_id)->toBe((int) $school->id)
        ->and($year->periods()->count())->toBe(0);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'school_id' => $school->id,
        'start_date' => '2031-02-08 00:00:00',
        'end_date' => '2031-11-27 00:00:00',
        'source' => 'School calendar register',
        'source_reference' => 'calendar-register/2027',
        'source_published_at' => '2026-11-01 00:00:00',
    ]);
});

it('rejects invalid academic year input', function (array $overrides): void {
    $school = School::factory()->create();

    expect(fn () => app(CreateAcademicYear::class)->execute($school, calendarYearData($overrides)))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('academic_years', 0);
})->with([
    'end before start' => [['start_date' => '2027-06-01', 'end_date' => '2027-01-01']],
    'equal dates' => [['start_date' => '2027-06-01', 'end_date' => '2027-06-01']],
    'missing source' => [['source' => '']],
    'blank source' => [['source' => '   ']],
    'unconfigured status' => [['status' => 'unverified-status']],
]);

it('keeps academic year names unique per school but not across schools', function (): void {
    $action = app(CreateAcademicYear::class);
    $firstSchool = School::factory()->create();
    $secondSchool = School::factory()->create();

    $first = $action->execute($firstSchool, calendarYearData(['name' => '2027']));
    $second = $action->execute($secondSchool, calendarYearData(['name' => '2027']));

    expect($first->name)->toBe($second->name);

    expect(fn () => $action->execute($firstSchool, calendarYearData(['name' => '2027'])))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('academic_years', 2);
});

it('makes academic year overlap configurable rather than assumed', function (): void {
    $school = School::factory()->create();
    $action = app(CreateAcademicYear::class);

    $action->execute($school, calendarYearData([
        'name' => 'First year',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]));

    expect(fn () => $action->execute($school, calendarYearData([
        'name' => 'Second year',
        'start_date' => '2027-06-01',
        'end_date' => '2028-05-31',
    ])))->not->toThrow(ValidationException::class);

    config()->set('academic-calendar.prevent_year_overlap', true);

    expect(fn () => $action->execute($school, calendarYearData([
        'name' => 'Third year',
        'start_date' => '2027-06-01',
        'end_date' => '2028-05-31',
    ])))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('academic_years', 2);
});

it('creates a configurable period inside a year and inherits the year school', function (): void {
    $school = School::factory()->create();
    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData());

    $period = app(CreateAcademicPeriod::class)->execute($school, calendarPeriodData($year, [
        'name' => '  Orientation Block  ',
        'type' => 'other',
        'sequence' => 7,
        'counting_basis' => 'calendar_days',
        'status' => 'published',
    ]));

    expect($period->exists)->toBeTrue()
        ->and($period->name)->toBe('Orientation Block')
        ->and($period->type)->toBe('other')
        ->and($period->sequence)->toBe(7)
        ->and($period->counting_basis)->toBe('calendar_days')
        ->and((int) $period->school_id)->toBe((int) $school->id)
        ->and((int) $period->academic_year_id)->toBe((int) $year->id);

    $this->assertDatabaseHas('academic_periods', [
        'id' => $period->id,
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'start_date' => '2027-01-10 00:00:00',
        'end_date' => '2027-03-20 00:00:00',
        'source' => 'School calendar register',
    ]);
});

it('rejects periods outside their year and invalid period dates', function (array $overrides): void {
    $school = School::factory()->create();
    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData());

    expect(fn () => app(CreateAcademicPeriod::class)->execute($school, calendarPeriodData($year, $overrides)))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('academic_periods', 0);
})->with([
    'starts before year' => [['start_date' => '2026-12-31', 'end_date' => '2027-02-01']],
    'ends after year' => [['start_date' => '2027-11-01', 'end_date' => '2028-01-01']],
    'end before start' => [['start_date' => '2027-05-01', 'end_date' => '2027-04-01']],
    'missing source' => [['source' => '']],
    'unconfigured type' => [['type' => 'unverified-type']],
    'unconfigured counting basis' => [['counting_basis' => 'unverified-basis']],
]);

it('rejects duplicate period names and sequences within the same year', function (string $field, mixed $value): void {
    $school = School::factory()->create();
    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData());
    $action = app(CreateAcademicPeriod::class);

    $action->execute($school, calendarPeriodData($year, [
        'name' => 'Learning Block A',
        'sequence' => 1,
        'start_date' => '2027-01-10',
        'end_date' => '2027-02-10',
    ]));

    expect(fn () => $action->execute($school, calendarPeriodData($year, [
        'name' => $field === 'name' ? $value : 'Learning Block B',
        'sequence' => $field === 'sequence' ? $value : 2,
        'start_date' => '2027-02-11',
        'end_date' => '2027-03-10',
    ])))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('academic_periods', 1);
})->with([
    'name' => ['name', 'Learning Block A'],
    'sequence' => ['sequence', 1],
]);

it('allows repeated period names and sequences in different years', function (): void {
    $school = School::factory()->create();
    $action = app(CreateAcademicYear::class);
    $firstYear = $action->execute($school, calendarYearData(['name' => 'First year']));
    $secondYear = $action->execute($school, calendarYearData(['name' => 'Second year']));

    $periodAction = app(CreateAcademicPeriod::class);
    $first = $periodAction->execute($school, calendarPeriodData($firstYear));
    $second = $periodAction->execute($school, calendarPeriodData($secondYear));

    expect($first->name)->toBe($second->name)
        ->and($first->sequence)->toBe($second->sequence)
        ->and($second->school_id)->toBe($first->school_id);

    $this->assertDatabaseCount('academic_periods', 2);
});

it('rejects overlapping periods but accepts adjacent boundaries', function (): void {
    $school = School::factory()->create();
    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData());
    $action = app(CreateAcademicPeriod::class);

    $action->execute($school, calendarPeriodData($year, [
        'name' => 'Block A',
        'sequence' => 1,
        'start_date' => '2027-01-05',
        'end_date' => '2027-03-20',
    ]));
    $action->execute($school, calendarPeriodData($year, [
        'name' => 'Block B',
        'sequence' => 2,
        'start_date' => '2027-03-21',
        'end_date' => '2027-06-20',
    ]));

    expect(fn () => $action->execute($school, calendarPeriodData($year, [
        'name' => 'Block C',
        'sequence' => 3,
        'start_date' => '2027-02-01',
        'end_date' => '2027-04-01',
    ])))->toThrow(ValidationException::class);

    config()->set('academic-calendar.prevent_period_overlap', false);

    expect(fn () => $action->execute($school, calendarPeriodData($year, [
        'name' => 'Block C',
        'sequence' => 3,
        'start_date' => '2027-02-01',
        'end_date' => '2027-04-01',
    ])))->not->toThrow(ValidationException::class);

    $this->assertDatabaseCount('academic_periods', 3);
});

it('updates years and periods while applying the same integrity rules', function (): void {
    $school = School::factory()->create();
    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData(['name' => 'Original year']));
    $period = app(CreateAcademicPeriod::class)->execute($school, calendarPeriodData($year));

    $updatedYear = app(UpdateAcademicYear::class)->execute($school, $year, calendarYearData([
        'name' => 'Renamed year',
        'start_date' => '2027-02-01',
        'end_date' => '2027-11-30',
    ]));

    $updatedPeriod = app(UpdateAcademicPeriod::class)->execute($school, $period, calendarPeriodData($updatedYear, [
        'name' => 'Renamed block',
        'sequence' => 2,
        'start_date' => '2027-02-10',
        'end_date' => '2027-04-10',
    ]));

    expect($updatedYear->is($year))->toBeTrue()
        ->and($updatedYear->name)->toBe('Renamed year')
        ->and($updatedPeriod->is($period))->toBeTrue()
        ->and($updatedPeriod->name)->toBe('Renamed block')
        ->and($updatedPeriod->sequence)->toBe(2);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'name' => 'Renamed year',
        'start_date' => '2027-02-01 00:00:00',
    ])->assertDatabaseHas('academic_periods', [
        'id' => $period->id,
        'name' => 'Renamed block',
        'sequence' => 2,
    ]);
});

it('rejects an update that duplicates another period sequence', function (): void {
    $school = School::factory()->create();
    $year = app(CreateAcademicYear::class)->execute($school, calendarYearData());
    $action = app(CreateAcademicPeriod::class);

    $first = $action->execute($school, calendarPeriodData($year, [
        'name' => 'Block A',
        'sequence' => 1,
        'start_date' => '2027-01-05',
        'end_date' => '2027-02-10',
    ]));
    $action->execute($school, calendarPeriodData($year, [
        'name' => 'Block B',
        'sequence' => 2,
        'start_date' => '2027-02-11',
        'end_date' => '2027-03-10',
    ]));

    expect(fn () => app(UpdateAcademicPeriod::class)->execute($school, $first, calendarPeriodData($year, [
        'name' => 'Block A',
        'sequence' => 2,
        'start_date' => '2027-01-05',
        'end_date' => '2027-02-10',
    ])))->toThrow(ValidationException::class);
});

it('orders periods by sequence through the relationship', function (): void {
    $year = AcademicYear::factory()->create();

    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Third',
        'sequence' => 3,
        'start_date' => '2027-09-01',
        'end_date' => '2027-12-01',
    ]);
    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'First',
        'sequence' => 1,
        'start_date' => '2027-01-01',
        'end_date' => '2027-04-01',
    ]);
    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Second',
        'sequence' => 2,
        'start_date' => '2027-04-02',
        'end_date' => '2027-08-31',
    ]);

    expect($year->periods()->pluck('name')->all())->toBe(['First', 'Second', 'Third']);
});

it('reports calendar validation problems from stored records', function (): void {
    config()->set('academic-calendar.prevent_period_overlap', true);

    $year = AcademicYear::factory()->create([
        'name' => 'Invalid year',
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-01',
    ]);

    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'Outside year',
        'sequence' => 1,
        'start_date' => '2027-02-01',
        'end_date' => '2027-03-01',
    ]);
    AcademicPeriod::factory()->inYear($year)->create([
        'name' => 'outside year',
        'sequence' => 2,
        'start_date' => '2027-02-15',
        'end_date' => '2027-03-15',
    ]);

    $issues = app(ValidateAcademicCalendar::class)->execute($year);

    expect($issues)->not->toBeEmpty()
        ->and(collect($issues)->some(fn (string $issue): bool => str_contains($issue, 'start date must precede')))->toBeTrue()
        ->and(collect($issues)->some(fn (string $issue): bool => str_contains($issue, 'falls outside its academic year')))->toBeTrue()
        ->and(collect($issues)->some(fn (string $issue): bool => str_contains($issue, 'duplicated in this academic year')))->toBeTrue()
        ->and(collect($issues)->some(fn (string $issue): bool => str_contains($issue, 'overlap')))->toBeTrue();
});
