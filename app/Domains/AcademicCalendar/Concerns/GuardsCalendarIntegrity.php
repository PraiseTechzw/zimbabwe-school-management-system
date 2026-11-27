<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Concerns;

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Shared integrity checks for academic calendar writes.
 *
 * Date order is validated in the actions because Blueprint has no portable
 * CHECK constraint support. All checks accept an ignored ID so the same rules
 * can be reused by update operations.
 */
trait GuardsCalendarIntegrity
{
    private function resolveYearForSchool(School $school, int $yearId): AcademicYear
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::query()->whereKey($yearId)->lockForUpdate()->firstOrFail();

        if ((int) $year->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'The academic year does not belong to this school.',
            ]);
        }

        return $year;
    }

    private function assertYearNameAvailable(School $school, string $name, ?int $ignoreId = null): void
    {
        $exists = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('name', $name)
            ->when($ignoreId !== null, function (Builder $query) use ($ignoreId): void {
                $query->whereKeyNot($ignoreId);
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'An academic year with this name is already recorded for this school.',
            ]);
        }
    }

    private function assertYearRangeAvailable(School $school, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        if (! (bool) config('academic-calendar.prevent_year_overlap', false)) {
            return;
        }

        $overlaps = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->when($ignoreId !== null, function (Builder $query) use ($ignoreId): void {
                $query->whereKeyNot($ignoreId);
            })
            ->lockForUpdate()
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'This academic year overlaps an existing year for this school.',
            ]);
        }
    }

    private function assertPeriodNameAvailable(AcademicYear $year, string $name, ?int $ignoreId = null): void
    {
        $exists = AcademicPeriod::query()
            ->where('academic_year_id', $year->id)
            ->where('name', $name)
            ->when($ignoreId !== null, function (Builder $query) use ($ignoreId): void {
                $query->whereKeyNot($ignoreId);
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A period with this name is already recorded for this academic year.',
            ]);
        }
    }

    private function assertPeriodSequenceAvailable(AcademicYear $year, int $sequence, ?int $ignoreId = null): void
    {
        $exists = AcademicPeriod::query()
            ->where('academic_year_id', $year->id)
            ->where('sequence', $sequence)
            ->when($ignoreId !== null, function (Builder $query) use ($ignoreId): void {
                $query->whereKeyNot($ignoreId);
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sequence' => 'This sequence number is already used in the academic year.',
            ]);
        }
    }

    private function assertPeriodWithinYear(AcademicYear $year, string $startDate, string $endDate): void
    {
        if (! (bool) config('academic-calendar.require_periods_within_year', true)) {
            return;
        }

        $yearStart = Carbon::parse($year->start_date)->toDateString();
        $yearEnd = Carbon::parse($year->end_date)->toDateString();

        if ($startDate < $yearStart || $endDate > $yearEnd) {
            throw ValidationException::withMessages([
                'start_date' => 'The period must fall within its academic year.',
            ]);
        }
    }

    private function assertPeriodRangeAvailable(AcademicYear $year, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        if (! (bool) config('academic-calendar.prevent_period_overlap', true)) {
            return;
        }

        $overlaps = AcademicPeriod::query()
            ->where('academic_year_id', $year->id)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->when($ignoreId !== null, function (Builder $query) use ($ignoreId): void {
                $query->whereKeyNot($ignoreId);
            })
            ->lockForUpdate()
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'This period overlaps another period in the academic year.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function calendarMetadata(array $data): array
    {
        return [
            'source' => $this->nullableString($data, 'source'),
            'source_reference' => $this->nullableString($data, 'source_reference'),
            'source_published_at' => isset($data['source_published_at']) ? (string) $data['source_published_at'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
