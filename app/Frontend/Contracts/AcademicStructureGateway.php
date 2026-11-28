<?php

declare(strict_types=1);

namespace App\Frontend\Contracts;

/**
 * Frontend boundary for the verified Academic Structure contract.
 *
 * Implementations must resolve tenancy from the server-side school context and
 * must never accept a client-supplied school identifier. Writes use the same
 * domain actions as the JSON API and remain subject to its permissions.
 */
interface AcademicStructureGateway
{
    public function canView(): bool;

    public function canManage(): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function levels(): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createLevel(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateLevel(int $educationalLevelId, array $data): array;

    /**
     * All classes in the school, aggregated for selectors and counts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allClasses(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function gradeLevels(): array;

    /**
     * The parent comes from the selected record, as on the nested API route.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createGrade(int $educationalLevelId, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateGrade(int $gradeLevelId, array $data): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function classes(int $gradeLevelId): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createClass(int $gradeLevelId, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateClass(int $classDefinitionId, array $data): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function streams(int $classDefinitionId): array;

    /**
     * All streams in the school, aggregated for selectors and counts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allStreams(): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createStream(int $classDefinitionId, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateStream(int $streamId, array $data): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function years(): array;

    /**
     * A year filter is passed only when the API's optional owned-year filter is used.
     *
     * @return array<int, array<string, mixed>>
     */
    public function offerings(?int $academicYearId = null): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createOffering(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateOffering(int $classOfferingId, array $data): array;
}
