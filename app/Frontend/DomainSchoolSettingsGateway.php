<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Actions\ListSchoolSettingRevisions;
use App\Domains\SchoolSettings\Actions\ResolveSchoolSettings;
use App\Domains\SchoolSettings\Actions\UpdateSchoolSettings;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use App\Frontend\Contracts\SchoolSettingsGateway;

/**
 * School settings adapter backed by the verified domain actions.
 *
 * Reads and writes use the authoritative SchoolContext, so the UI never sends
 * a client-supplied school_id or invents a setting that the API does not have.
 */
final class DomainSchoolSettingsGateway implements SchoolSettingsGateway
{
    public function __construct(
        private readonly SchoolContext $context,
        private readonly ResolveSchoolSettings $resolveAction,
        private readonly UpdateSchoolSettings $updateAction,
        private readonly ListSchoolSettingRevisions $revisionsAction,
    ) {}

    public function current(): array
    {
        $school = $this->school();

        /** @var SchoolSetting $setting */
        $setting = $this->resolveAction->execute($school);

        return $this->payload($setting, $this->hasSavedSettings($school));
    }

    public function revisions(): array
    {
        /** @var list<array<string, mixed>> $revisions */
        $revisions = $this->revisionsAction->execute($this->school());

        return $revisions;
    }

    public function update(array $data): array
    {
        $school = $this->school();
        $setting = $this->updateAction->execute($school, $data);

        return $this->payload($setting, true);
    }

    private function school(): School
    {
        return $this->context->assertCurrent();
    }

    private function hasSavedSettings(School $school): bool
    {
        return SchoolSetting::query()->forSchool($school)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(SchoolSetting $setting, bool $persisted): array
    {
        $source = $setting->getRawOriginal('source');
        $effectiveFrom = $setting->getRawOriginal('effective_from');

        return [
            'school_id' => (int) $setting->school_id,
            'settings' => $setting->values(),
            'revision' => [
                'version' => (int) $setting->version,
                'source' => $source === null ? null : (string) $source,
                'effective_from' => $effectiveFrom === null ? null : (string) $effectiveFrom,
            ],
            'persisted' => $persisted,
        ];
    }
}
