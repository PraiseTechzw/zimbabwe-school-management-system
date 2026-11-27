<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\AcademicCalendar\Actions\CreateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\CreateAcademicYear;
use App\Domains\AcademicCalendar\Actions\ResolveCurrentAcademicYear;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicPeriod;
use App\Domains\AcademicCalendar\Actions\UpdateAcademicYear;
use App\Domains\AcademicCalendar\Actions\ValidateAcademicCalendar;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Context\SchoolContext;
use App\Http\Requests\AcademicCalendar\StoreAcademicPeriodRequest;
use App\Http\Requests\AcademicCalendar\StoreAcademicYearRequest;
use App\Http\Requests\AcademicCalendar\UpdateAcademicPeriodRequest;
use App\Http\Requests\AcademicCalendar\UpdateAcademicYearRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

final class AcademicCalendarController extends Controller
{
    public function current(Request $request, SchoolContext $context): JsonResponse
    {
        Validator::make($request->all(), [
            'school_id' => ['prohibited'],
        ])->validate();

        $school = $context->assertCurrent();
        $year = app(ResolveCurrentAcademicYear::class)->execute($school);

        return $this->tenantJson([
            'school_id' => (int) $school->id,
            'academic_year' => $year === null ? null : $this->yearPayload($year),
        ]);
    }

    public function years(SchoolContext $context): JsonResponse
    {
        $school = $context->assertCurrent();

        /** @var array<int, array<string, mixed>> $years */
        $years = AcademicYear::query()
            ->forCurrentSchool()
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (AcademicYear $year): array => $this->yearPayload($year))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $school->id,
            'academic_years' => $years,
        ]);
    }

    public function storeYear(StoreAcademicYearRequest $request, SchoolContext $context): JsonResponse
    {
        $year = app(CreateAcademicYear::class)->execute($context->assertCurrent(), $request->validated());

        return $this->tenantJson(['academic_year' => $this->yearPayload($year)], Response::HTTP_CREATED);
    }

    public function updateYear(UpdateAcademicYearRequest $request, SchoolContext $context, int $academicYear): JsonResponse
    {
        $year = $this->scopedYear($academicYear);
        $updated = app(UpdateAcademicYear::class)->execute($context->assertCurrent(), $year, $request->validated());

        return $this->tenantJson(['academic_year' => $this->yearPayload($updated)]);
    }

    public function periods(SchoolContext $context, int $academicYear): JsonResponse
    {
        $year = $this->scopedYear($academicYear);

        /** @var array<int, array<string, mixed>> $periods */
        $periods = AcademicPeriod::query()
            ->forCurrentSchool()
            ->where('academic_year_id', $year->id)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->map(fn (AcademicPeriod $period): array => $this->periodPayload($period))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $year->school_id,
            'academic_year_id' => (int) $year->id,
            'periods' => $periods,
        ]);
    }

    public function storePeriod(StoreAcademicPeriodRequest $request, SchoolContext $context, int $academicYear): JsonResponse
    {
        $year = $this->scopedYear($academicYear);
        $data = array_merge($request->validated(), ['academic_year_id' => (int) $year->id]);
        $period = app(CreateAcademicPeriod::class)->execute($context->assertCurrent(), $data);

        return $this->tenantJson(['period' => $this->periodPayload($period)], Response::HTTP_CREATED);
    }

    public function updatePeriod(UpdateAcademicPeriodRequest $request, SchoolContext $context, int $academicPeriod): JsonResponse
    {
        $period = $this->scopedPeriod($academicPeriod);
        $updated = app(UpdateAcademicPeriod::class)->execute($context->assertCurrent(), $period, $request->validated());

        return $this->tenantJson(['period' => $this->periodPayload($updated)]);
    }

    public function validateCalendar(Request $request, SchoolContext $context): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'school_id' => ['prohibited'],
            'academic_year_id' => ['required', 'integer'],
        ])->validate();

        $year = $this->scopedYear((int) $validated['academic_year_id']);
        $issues = app(ValidateAcademicCalendar::class)->execute($year);

        return $this->tenantJson([
            'school_id' => (int) $context->assertCurrent()->id,
            'academic_year_id' => (int) $year->id,
            'valid' => $issues === [],
            'issues' => $issues,
        ]);
    }

    private function scopedYear(int $id): AcademicYear
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::query()->forCurrentSchool()->findOrFail($id);

        return $year;
    }

    private function scopedPeriod(int $id): AcademicPeriod
    {
        /** @var AcademicPeriod $period */
        $period = AcademicPeriod::query()->forCurrentSchool()->findOrFail($id);

        return $period;
    }

    /**
     * @return array<string, mixed>
     */
    private function yearPayload(AcademicYear $year): array
    {
        return [
            'id' => (int) $year->id,
            'school_id' => (int) $year->school_id,
            'name' => $year->name,
            'start_date' => Carbon::parse($year->start_date)->toDateString(),
            'end_date' => Carbon::parse($year->end_date)->toDateString(),
            'status' => $year->status,
            'source' => $year->source,
            'source_reference' => $year->source_reference,
            'source_published_at' => $year->source_published_at === null
                ? null
                : Carbon::parse($year->source_published_at)->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodPayload(AcademicPeriod $period): array
    {
        return [
            'id' => (int) $period->id,
            'school_id' => (int) $period->school_id,
            'academic_year_id' => (int) $period->academic_year_id,
            'name' => $period->name,
            'type' => $period->type,
            'sequence' => (int) $period->sequence,
            'start_date' => Carbon::parse($period->start_date)->toDateString(),
            'end_date' => Carbon::parse($period->end_date)->toDateString(),
            'status' => $period->status,
            'counting_basis' => $period->counting_basis,
            'source' => $period->source,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function tenantJson(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json($payload, $status)
            ->header('Cache-Control', 'no-store')
            ->header('Vary', 'X-School-Id, school_id');
    }
}
