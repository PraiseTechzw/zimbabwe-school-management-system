<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Concerns;

use App\Domains\School\Models\School;
use Carbon\Carbon;

/**
 * Shared integrity helpers for settings writes.
 */
trait GuardsSchoolSettingsIntegrity
{
    /**
     * Serialise settings writes for one school on databases that support
     * SELECT ... FOR UPDATE. SQLite serialises writers at the engine level.
     */
    private function lockSettingsForSchool(School $school): School
    {
        /** @var School $locked */
        $locked = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();

        return $locked;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{locale: string, timezone: string, source: string, effective_from: string}
     */
    private function normaliseSettings(array $data): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('school-settings.defaults', []);

        $locale = isset($data['locale']) && is_string($data['locale']) && $data['locale'] !== ''
            ? $data['locale']
            : (string) ($defaults['locale'] ?? 'en');

        $timezone = isset($data['timezone']) && is_string($data['timezone']) && $data['timezone'] !== ''
            ? $data['timezone']
            : (string) ($defaults['timezone'] ?? 'UTC');

        $effectiveFrom = isset($data['effective_from']) && is_string($data['effective_from']) && $data['effective_from'] !== ''
            ? $data['effective_from']
            : Carbon::today()->toDateString();

        return [
            'locale' => $locale,
            'timezone' => $timezone,
            'source' => trim((string) ($data['source'] ?? '')),
            'effective_from' => $effectiveFrom,
        ];
    }
}
