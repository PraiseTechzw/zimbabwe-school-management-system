<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Actions;

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use Carbon\Carbon;

final class ValidateAcademicCalendar
{
    /**
     * Return human-readable configuration problems; an empty list means valid.
     *
     * @return array<int, string>
     */
    public function execute(AcademicYear $year): array
    {
        $issues = [];

        $yearStart = Carbon::parse($year->start_date);
        $yearEnd = Carbon::parse($year->end_date);

        if ($yearStart->greaterThanOrEqualTo($yearEnd)) {
            $issues[] = 'The academic year start date must precede its end date.';
        }

        /** @var array<string, bool> $names */
        $names = [];

        /** @var array<int, bool> $sequences */
        $sequences = [];

        /** @var array<int, AcademicPeriod> $periods */
        $periods = $year->periods()->get()->all();

        foreach ($periods as $period) {
            $key = mb_strtolower($period->name);

            if (isset($names[$key])) {
                $issues[] = 'Period name "'.$period->name.'" is duplicated in this academic year.';
            }

            $names[$key] = true;

            if (isset($sequences[$period->sequence])) {
                $issues[] = 'Period sequence '.$period->sequence.' is duplicated in this academic year.';
            }

            $sequences[$period->sequence] = true;

            $periodStart = Carbon::parse($period->start_date);
            $periodEnd = Carbon::parse($period->end_date);

            if ($periodStart->greaterThanOrEqualTo($periodEnd)) {
                $issues[] = 'Period "'.$period->name.'" has an invalid date range.';
            }

            if ((bool) config('academic-calendar.require_periods_within_year', true)
                && ($periodStart->lt($yearStart) || $periodEnd->gt($yearEnd))) {
                $issues[] = 'Period "'.$period->name.'" falls outside its academic year.';
            }
        }

        if ((bool) config('academic-calendar.prevent_period_overlap', true)) {
            foreach ($periods as $index => $period) {
                foreach (array_slice($periods, $index + 1) as $other) {
                    if (Carbon::parse($period->start_date)->lte(Carbon::parse($other->end_date))
                        && Carbon::parse($other->start_date)->lte(Carbon::parse($period->end_date))) {
                        $issues[] = 'Periods "'.$period->name.'" and "'.$other->name.'" overlap.';
                    }
                }
            }
        }

        return $issues;
    }
}
