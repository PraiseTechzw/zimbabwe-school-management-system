<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Actions;

use App\Domains\AcademicCalendar\Concerns\GuardsCalendarIntegrity;
use App\Domains\AcademicCalendar\Concerns\RecordsCalendarAudit;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateAcademicYear
{
    use GuardsCalendarIntegrity;
    use RecordsCalendarAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, AcademicYear $year, array $data): AcademicYear
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

        return DB::transaction(function () use ($school, $year, $validated, $name, $startDate, $endDate): AcademicYear {
            $this->lockCalendarForSchool($school);

            /** @var AcademicYear $current */
            $current = AcademicYear::query()->whereKey($year->id)->lockForUpdate()->firstOrFail();

            if ((int) $current->school_id !== (int) $school->id) {
                throw new AuthorizationException('The academic year is outside the authorised school.');
            }

            $this->assertYearNameAvailable($school, $name, (int) $current->id);
            $this->assertYearRangeAvailable($school, $startDate, $endDate, (int) $current->id);
            $this->assertPeriodsFitYear($current, $startDate, $endDate);

            $before = $this->yearAuditSnapshot($current);

            $current->update(array_merge([
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => isset($validated['status']) ? (string) $validated['status'] : $current->status,
            ], $this->calendarMetadata($validated)));

            $this->recordCalendarAudit(
                $school,
                'academic_year.updated',
                'academic_year',
                (string) $current->id,
                $before,
                $this->yearAuditSnapshot($current),
                [
                    'source' => (string) $current->source,
                    'source_reference' => $current->source_reference === null ? null : (string) $current->source_reference,
                ],
            );

            return $current;
        });
    }
}
