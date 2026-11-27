<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Actions;

use App\Domains\AcademicCalendar\Concerns\GuardsCalendarIntegrity;
use App\Domains\AcademicCalendar\Concerns\RecordsCalendarAudit;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\School\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateAcademicPeriod
{
    use GuardsCalendarIntegrity;
    use RecordsCalendarAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, AcademicPeriod $period, array $data): AcademicPeriod
    {
        $validated = Validator::make($data, [
            'academic_year_id' => ['sometimes', 'required', 'integer', 'exists:academic_years,id'],
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

        $name = trim((string) $validated['name']);
        $startDate = (string) $validated['start_date'];
        $endDate = (string) $validated['end_date'];
        $sequence = (int) $validated['sequence'];

        return DB::transaction(function () use ($school, $period, $validated, $name, $startDate, $endDate, $sequence): AcademicPeriod {
            $this->lockCalendarForSchool($school);

            /** @var AcademicPeriod $current */
            $current = AcademicPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if ((int) $current->school_id !== (int) $school->id) {
                throw new AuthorizationException('The period is outside the authorised school.');
            }

            $yearId = isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : (int) $current->academic_year_id;
            $year = $this->resolveYearForSchool($school, $yearId);

            $this->assertPeriodNameAvailable($year, $name, (int) $current->id);
            $this->assertPeriodSequenceAvailable($year, $sequence, (int) $current->id);
            $this->assertPeriodWithinYear($year, $startDate, $endDate);
            $this->assertPeriodRangeAvailable($year, $startDate, $endDate, (int) $current->id);

            $before = $this->periodAuditSnapshot($current);

            $current->update(array_merge([
                'academic_year_id' => $year->id,
                'name' => $name,
                'type' => (string) $validated['type'],
                'sequence' => $sequence,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => isset($validated['status']) ? (string) $validated['status'] : $current->status,
                'counting_basis' => isset($validated['counting_basis']) ? (string) $validated['counting_basis'] : $current->counting_basis,
            ], $this->calendarMetadata($validated)));

            $this->recordCalendarAudit(
                $school,
                'academic_period.updated',
                'academic_period',
                (string) $current->id,
                $before,
                $this->periodAuditSnapshot($current),
                [
                    'source' => (string) $current->source,
                    'source_reference' => $current->source_reference === null ? null : (string) $current->source_reference,
                ],
            );

            return $current;
        });
    }
}
