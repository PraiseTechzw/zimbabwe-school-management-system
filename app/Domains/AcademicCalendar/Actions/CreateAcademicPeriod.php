<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Actions;

use App\Domains\AcademicCalendar\Concerns\GuardsCalendarIntegrity;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateAcademicPeriod
{
    use GuardsCalendarIntegrity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): AcademicPeriod
    {
        $validated = Validator::make($data, [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys((array) config('academic-calendar.period_types', [])))],
            'sequence' => ['required', 'integer', 'min:1', 'max:10000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'status' => ['nullable', Rule::in(array_keys((array) config('academic-calendar.period_statuses', [])))],
            'counting_basis' => ['nullable', Rule::in(array_keys((array) config('academic-calendar.counting_bases', [])))],
            'source' => ['required', 'string', 'max:255', 'not_regex:/^\s*$/'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_published_at' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        $yearId = (int) $validated['academic_year_id'];
        $name = trim((string) $validated['name']);
        $startDate = (string) $validated['start_date'];
        $endDate = (string) $validated['end_date'];
        $sequence = (int) $validated['sequence'];

        return DB::transaction(function () use ($school, $validated, $yearId, $name, $startDate, $endDate, $sequence): AcademicPeriod {
            $this->lockCalendarForSchool($school);
            $year = $this->resolveYearForSchool($school, $yearId);

            $this->assertPeriodNameAvailable($year, $name);
            $this->assertPeriodSequenceAvailable($year, $sequence);
            $this->assertPeriodWithinYear($year, $startDate, $endDate);
            $this->assertPeriodRangeAvailable($year, $startDate, $endDate);

            $period = new AcademicPeriod;
            $period->fill(array_merge([
                'academic_year_id' => $year->id,
                'name' => $name,
                'type' => (string) $validated['type'],
                'sequence' => $sequence,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => isset($validated['status']) ? (string) $validated['status'] : 'draft',
                'counting_basis' => isset($validated['counting_basis']) ? (string) $validated['counting_basis'] : null,
            ], $this->calendarMetadata($validated)));
            $period->school_id = $school->id;
            $period->save();

            return $period;
        });
    }
}
