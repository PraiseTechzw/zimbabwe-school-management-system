<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Actions;

use App\Domains\AcademicCalendar\Concerns\GuardsCalendarIntegrity;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateAcademicYear
{
    use GuardsCalendarIntegrity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): AcademicYear
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'status' => ['nullable', Rule::in(array_keys((array) config('academic-calendar.year_statuses', [])))],
            'source' => ['required', 'string', 'max:255', 'not_regex:/^\s*$/'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_published_at' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        $name = trim((string) $validated['name']);
        $startDate = (string) $validated['start_date'];
        $endDate = (string) $validated['end_date'];

        return DB::transaction(function () use ($school, $validated, $name, $startDate, $endDate): AcademicYear {
            $this->assertYearNameAvailable($school, $name);
            $this->assertYearRangeAvailable($school, $startDate, $endDate);

            return AcademicYear::query()->create(array_merge([
                'school_id' => $school->id,
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => isset($validated['status']) ? (string) $validated['status'] : 'draft',
            ], $this->calendarMetadata($validated)));
        });
    }
}
