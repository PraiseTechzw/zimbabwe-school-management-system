<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Domains\AcademicCalendar\Actions\CreateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicYear;
use App\Domains\AcademicCalendar\Actions\ValidateAcademicCalendar;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Frontend\Contracts\CalendarManagementGateway;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * Calendar adapter backed by the verified domain actions in this application.
 *
 * Reads use the same school scoping as the JSON API; writes go through the
 * actions that the API controllers use. Writes also apply the configured
 * academic_calendar.manage boundary so Livewire updates cannot bypass the
 * route-level permission gate.
 */
final class DomainCalendarManagementGateway implements CalendarManagementGateway
{
    public function __construct(
        private readonly SchoolContext $context,
        private readonly SchoolPermissions $permissions,
        private readonly CreateAcademicYear $createYearAction,
        private readonly UpdateAcademicYear $updateYearAction,
        private readonly CreateAcademicPeriod $createPeriodAction,
        private readonly UpdateAcademicPeriod $updatePeriodAction,
        private readonly ValidateAcademicCalendar $validateCalendarAction,
    ) {}

    public function years(): array
    {
        $years = [];

        foreach (AcademicYear::query()->forCurrentSchool()->orderBy('start_date')->orderBy('id')->get() as $year) {
            $years[] = $this->yearPayload($year);
        }

        return $years;
    }

    public function periods(int $academicYearId): array
    {
        $year = $this->year($academicYearId);
        $periods = [];

        foreach (AcademicPeriod::query()
            ->forCurrentSchool()
            ->where('academic_year_id', $year->id)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get() as $period) {
            $periods[] = $this->periodPayload($period);
        }

        return $periods;
    }

    public function createYear(array $data): array
    {
        $this->assertCanManage();

        return $this->yearPayload($this->createYearAction->execute($this->school(), $data));
    }

    public function updateYear(int $academicYearId, array $data): array
    {
        $this->assertCanManage();

        return $this->yearPayload($this->updateYearAction->execute(
            $this->school(),
            $this->year($academicYearId),
            $data,
        ));
    }

    public function createPeriod(int $academicYearId, array $data): array
    {
        $this->assertCanManage();

        // The selected year is authoritative, just as the route ID is on the API.
        $data['academic_year_id'] = $academicYearId;

        return $this->periodPayload($this->createPeriodAction->execute($this->school(), $data));
    }

    public function updatePeriod(int $academicPeriodId, array $data): array
    {
        $this->assertCanManage();

        return $this->periodPayload($this->updatePeriodAction->execute(
            $this->school(),
            $this->period($academicPeriodId),
            $data,
        ));
    }

    public function validateYear(int $academicYearId): array
    {
        $issues = $this->validateCalendarAction->execute($this->year($academicYearId));

        return [
            'valid' => $issues === [],
            'issues' => $issues,
        ];
    }

    private function assertCanManage(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $this->permissions->has($user, $this->school(), 'academic_calendar.manage')) {
            throw new AuthorizationException('The user does not have permission to manage academic calendars.');
        }
    }

    private function school(): School
    {
        return $this->context->assertCurrent();
    }

    private function year(int $academicYearId): AcademicYear
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::query()->forCurrentSchool()->findOrFail($academicYearId);

        return $year;
    }

    private function period(int $academicPeriodId): AcademicPeriod
    {
        /** @var AcademicPeriod $period */
        $period = AcademicPeriod::query()->forCurrentSchool()->findOrFail($academicPeriodId);

        return $period;
    }

    /**
     * @return array<string, mixed>
     */
    private function yearPayload(AcademicYear $year): array
    {
        return [
            'id' => (int) $year->id,
            'name' => (string) $year->name,
            'start_date' => Carbon::parse((string) $year->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse((string) $year->end_date)->format('Y-m-d'),
            'status' => (string) $year->status,
            'source' => $this->stringOrNull($year->source),
            'source_reference' => $this->stringOrNull($year->source_reference),
            'source_published_at' => $year->source_published_at === null
                ? null
                : Carbon::parse((string) $year->source_published_at)->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodPayload(AcademicPeriod $period): array
    {
        return [
            'id' => (int) $period->id,
            'academic_year_id' => (int) $period->academic_year_id,
            'name' => (string) $period->name,
            'type' => (string) $period->type,
            'sequence' => (int) $period->sequence,
            'start_date' => Carbon::parse((string) $period->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse((string) $period->end_date)->format('Y-m-d'),
            'status' => (string) $period->status,
            'counting_basis' => $this->stringOrNull($period->counting_basis),
            'source' => $this->stringOrNull($period->source),
            'source_reference' => $this->stringOrNull($period->source_reference),
            'source_published_at' => $period->source_published_at === null
                ? null
                : Carbon::parse((string) $period->source_published_at)->format('Y-m-d'),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
