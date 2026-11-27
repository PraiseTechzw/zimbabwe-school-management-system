<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Actions;

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Carbon\Carbon;

/**
 * Resolves one authoritative current year for a school.
 *
 * The rule is intentionally deterministic: only configured current statuses
 * whose inclusive date range contains today qualify; ties are broken by the
 * latest start date and then the highest ID. No implicit fallback is applied.
 */
final class ResolveCurrentAcademicYear
{
    public function execute(School $school): ?AcademicYear
    {
        /** @var array<int, string> $statuses */
        $statuses = array_keys((array) config('academic-calendar.current_year_statuses', ['published']));

        if ($statuses === []) {
            return null;
        }

        $today = Carbon::today()->toDateString();

        /** @var AcademicYear|null $year */
        $year = AcademicYear::query()
            ->forSchool($school)
            ->whereIn('status', $statuses)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $year;
    }
}
