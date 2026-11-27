<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Domains\AcademicCalendar\Actions\ResolveCurrentAcademicYear;
use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\SchoolIdentifier;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side read adapter matching the verified JSON API payloads.
 *
 * Screens are server-rendered, so reading the same authoritative queries in
 * process avoids a redundant HTTP round trip while keeping the response
 * shapes identical to /api/* contracts.
 */
final class ServerFrontendDataProvider implements FrontendDataProvider
{
    public function __construct(private readonly SchoolContext $context) {}

    public function user(): ?array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
        ];
    }

    public function school(): ?array
    {
        $school = $this->context->current();

        if ($school === null) {
            return null;
        }

        return [
            'id' => (int) $school->getKey(),
            'name' => $school->name,
            'type' => $school->type,
        ];
    }

    public function identifiers(): array
    {
        if ($this->context->id() === null) {
            return [];
        }

        /** @var list<array{id: int, type: string, value: string, verification_status: string}> */
        return SchoolIdentifier::query()
            ->forCurrentSchool()
            ->orderBy('id')
            ->get(['id', 'type', 'value', 'verification_status'])
            ->map(static fn (SchoolIdentifier $identifier): array => [
                'id' => (int) $identifier->getKey(),
                'type' => $identifier->type,
                'value' => $identifier->value,
                'verification_status' => $identifier->verification_status,
            ])
            ->all();
    }

    public function currentYear(): ?array
    {
        $school = $this->context->current();

        if ($school === null) {
            return null;
        }

        $year = app(ResolveCurrentAcademicYear::class)->execute($school);

        return $year === null ? null : $this->yearPayload($year);
    }

    public function years(): array
    {
        if ($this->context->id() === null) {
            return [];
        }

        /** @var list<array{id: int, name: string, start_date: string, end_date: string, status: string, source: string}> */
        return AcademicYear::query()
            ->forCurrentSchool()
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (AcademicYear $year): array => $this->yearPayload($year))
            ->all();
    }

    public function periods(int $academicYearId): array
    {
        if ($this->context->id() === null || ! $this->yearExistsInContext($academicYearId)) {
            return [];
        }

        /** @var list<array{id: int, name: string, type: string, sequence: int, start_date: string, end_date: string, status: string}> */
        return AcademicPeriod::query()
            ->forCurrentSchool()
            ->where('academic_year_id', $academicYearId)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->map(static fn (AcademicPeriod $period): array => [
                'id' => (int) $period->getKey(),
                'name' => $period->name,
                'type' => $period->type,
                'sequence' => (int) $period->sequence,
                'start_date' => Carbon::parse($period->start_date)->toDateString(),
                'end_date' => Carbon::parse($period->end_date)->toDateString(),
                'status' => $period->status,
            ])
            ->all();
    }

    public function attention(): array
    {
        // No verified endpoint exists for cross-module aggregates.
        return [];
    }

    /**
     * @return array{id: int, name: string, start_date: string, end_date: string, status: string, source: string}
     */
    private function yearPayload(AcademicYear $year): array
    {
        return [
            'id' => (int) $year->getKey(),
            'name' => $year->name,
            'start_date' => Carbon::parse($year->start_date)->toDateString(),
            'end_date' => Carbon::parse($year->end_date)->toDateString(),
            'status' => $year->status,
            'source' => $year->source,
        ];
    }

    private function yearExistsInContext(int $academicYearId): bool
    {
        return AcademicYear::query()
            ->forCurrentSchool()
            ->whereKey($academicYearId)
            ->exists();
    }
}
