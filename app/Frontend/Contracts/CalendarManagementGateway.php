<?php

declare(strict_types=1);

namespace App\Frontend\Contracts;

/**
 * Frontend boundary for calendar read/write operations.
 *
 * The implementation must resolve tenancy from the server-side school context.
 * It must never accept a client-supplied school identifier.
 */
interface CalendarManagementGateway
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function years(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function periods(int $academicYearId): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createYear(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateYear(int $academicYearId, array $data): array;

    /**
     * The academic year is taken from the selected record, not from request data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createPeriod(int $academicYearId, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updatePeriod(int $academicPeriodId, array $data): array;

    /**
     * @return array{valid: bool, issues: array<int, string>}
     */
    public function validateYear(int $academicYearId): array;
}
