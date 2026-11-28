<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domains\School\Context\SchoolContext;
use App\Frontend\Contracts\CalendarManagementGateway;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class AcademicCalendarViewController extends Controller
{
    public function __construct(
        private readonly FrontendDataProvider $data,
        private readonly CalendarManagementGateway $calendar,
        private readonly SchoolContext $context,
    ) {}

    public function index(): View
    {
        $years = $this->data->years();
        $selectedId = (int) request()->query('year', (string) ($years[0]['id'] ?? 0));
        $selected = null;

        foreach ($years as $year) {
            if ($year['id'] === $selectedId) {
                $selected = $year;
                break;
            }
        }

        return view('frontend.academic-calendar', [
            'title' => 'Academic calendar',
            'user' => $this->data->user(),
            'school' => $this->data->school(),
            'academicYear' => $this->data->currentYear(),
            'years' => $years,
            'selectedYear' => $selected,
            'periods' => $selected === null ? [] : $this->data->periods($selected['id']),
            'validation' => $selected === null ? null : $this->calendar->validateYear((int) $selected['id']),
            'canManageCalendar' => $this->hasSchoolPermission(
                $this->context->assertCurrent(),
                'academic_calendar.manage',
            ),
        ]);
    }
}
