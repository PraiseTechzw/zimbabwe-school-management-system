<?php

declare(strict_types=1);

namespace App\Domains\LearnerManagement\Concerns;

use App\Domains\AuditTrail\Actions\RecordAuditEvent;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Models\School;

/**
 * Deliberate, privacy-minimised audit integration for learner writes.
 *
 * Snapshots never contain the learner's name, date of birth or internal
 * reference. Presence flags are sufficient to establish that a value changed
 * without creating a second copy of personal data.
 */
trait RecordsLearnerAudit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordLearnerAudit(
        School $school,
        string $action,
        Learner $learner,
        ?array $before,
        ?array $after,
    ): void {
        app(RecordAuditEvent::class)->execute(
            AuditScope::forSchool($school),
            new AuditEventData(
                action: $action,
                resourceType: 'learner',
                resourceId: (string) $learner->id,
                category: 'learner_record',
                metadata: [],
                changes: [
                    'before' => $before,
                    'after' => $after,
                ],
                source: 'domain',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function learnerAuditSnapshot(Learner $learner): array
    {
        return [
            'status' => (string) $learner->status,
            'full_name_present' => trim((string) $learner->full_name) !== '',
            'date_of_birth_present' => $learner->date_of_birth !== null && $learner->date_of_birth !== '',
            'class_offering_id' => $learner->class_offering_id === null
                ? null
                : (int) $learner->class_offering_id,
        ];
    }
}
