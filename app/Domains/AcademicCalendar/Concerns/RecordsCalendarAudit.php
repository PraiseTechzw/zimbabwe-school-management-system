<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Concerns;

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AuditTrail\Actions\RecordAuditEvent;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\School\Models\School;

/**
 * Deliberate audit integration for configuration-like calendar writes.
 *
 * Only explicitly selected metadata and change summaries are recorded; no
 * request body or model-wide attribute dump is used.
 */
trait RecordsCalendarAudit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    private function recordCalendarAudit(
        School $school,
        string $action,
        string $resourceType,
        string $resourceId,
        ?array $before,
        ?array $after,
        array $metadata,
    ): void {
        app(RecordAuditEvent::class)->execute(
            AuditScope::forSchool($school),
            new AuditEventData(
                action: $action,
                resourceType: $resourceType,
                resourceId: $resourceId,
                category: 'school_configuration',
                metadata: $metadata,
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
    private function yearAuditSnapshot(AcademicYear $year): array
    {
        return [
            'name' => (string) $year->name,
            'start_date' => (string) $year->start_date,
            'end_date' => (string) $year->end_date,
            'status' => (string) $year->status,
            'source' => (string) $year->source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodAuditSnapshot(AcademicPeriod $period): array
    {
        return [
            'academic_year_id' => (int) $period->academic_year_id,
            'name' => (string) $period->name,
            'type' => (string) $period->type,
            'sequence' => (int) $period->sequence,
            'start_date' => (string) $period->start_date,
            'end_date' => (string) $period->end_date,
            'status' => (string) $period->status,
            'counting_basis' => $period->counting_basis === null ? null : (string) $period->counting_basis,
            'source' => (string) $period->source,
        ];
    }
}
