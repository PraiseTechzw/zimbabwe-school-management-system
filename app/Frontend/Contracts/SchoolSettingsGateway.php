<?php

declare(strict_types=1);

namespace App\Frontend\Contracts;

/**
 * Frontend write boundary for school settings.
 *
 * Payload shapes mirror the verified /api/school-settings endpoints. The
 * `persisted` flag is an internal UI hint that distinguishes saved settings
 * from the defaults returned by a first read; it is not a persisted field.
 */
interface SchoolSettingsGateway
{
    /**
     * @return array<string, mixed>
     */
    public function current(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function revisions(): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array;
}
