<?php

declare(strict_types=1);

namespace App\Frontend\Contracts;

/**
 * Read model for frontend screens.
 *
 * Every method mirrors a verified API response shape from
 * docs/frontend/api-dependencies.md. Screens never reach into domain models
 * directly, so a future HTTP/API-client implementation can replace the
 * server-side adapter without changing views.
 *
 * @phpstan-type UserPayload array{id: int, name: string, email: string, status: string}
 * @phpstan-type SchoolPayload array{id: int, name: string, type: string}
 * @phpstan-type IdentifierPayload array{id: int, type: string, value: string, verification_status: string}
 * @phpstan-type YearPayload array{id: int, name: string, start_date: string, end_date: string, status: string, source: string}
 * @phpstan-type PeriodPayload array{id: int, name: string, type: string, sequence: int, start_date: string, end_date: string, status: string}
 * @phpstan-type AttentionPayload array{label: string, value: string, is_demo: bool}
 */
interface FrontendDataProvider
{
    /** @return UserPayload|null */
    public function user(): ?array;

    /** @return SchoolPayload|null */
    public function school(): ?array;

    /** @return list<IdentifierPayload> */
    public function identifiers(): array;

    /** @return YearPayload|null */
    public function currentYear(): ?array;

    /** @return list<YearPayload> */
    public function years(): array;

    /** @return list<PeriodPayload> */
    public function periods(int $academicYearId): array;

    /**
     * Cross-module items shown on the dashboard. No aggregate endpoint exists
     * yet, so the default implementation returns an empty list.
     *
     * @return list<AttentionPayload>
     */
    public function attention(): array;
}
