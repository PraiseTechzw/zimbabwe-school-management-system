<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Concerns;

use App\Domains\AuditTrail\Actions\RecordAuditEvent;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSetting;

/**
 * Deliberate audit integration for school configuration writes.
 *
 * Snapshots contain the persisted scalar settings fields only. No request
 * body, tenant identifier or model-wide attribute dump is copied into the
 * audit payload.
 */
trait RecordsSchoolSettingsAudit
{
    /**
     * @param  array<string, mixed>|null  $before
     */
    private function recordSchoolSettingsAudit(
        School $school,
        string $action,
        SchoolSetting $setting,
        ?array $before,
    ): void {
        app(RecordAuditEvent::class)->execute(
            AuditScope::forSchool($school),
            new AuditEventData(
                action: $action,
                resourceType: 'school_setting',
                resourceId: (string) $setting->id,
                category: 'school_configuration',
                changes: [
                    'before' => $before,
                    'after' => $this->schoolSettingAuditSnapshot($setting),
                ],
                source: 'domain',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolSettingAuditSnapshot(SchoolSetting $setting): array
    {
        return $setting->snapshot();
    }
}
