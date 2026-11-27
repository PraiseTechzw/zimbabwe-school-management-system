<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Concerns;

use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\AuditTrail\Actions\RecordAuditEvent;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\School\Models\School;

/**
 * Deliberate audit integration for structure configuration writes.
 *
 * Snapshots contain selected scalar fields only. School-defined configuration
 * payloads are represented by a presence flag rather than copied verbatim.
 */
trait RecordsAcademicStructureAudit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordStructureAudit(
        School $school,
        string $action,
        string $resourceType,
        string $resourceId,
        ?array $before,
        ?array $after,
    ): void {
        app(RecordAuditEvent::class)->execute(
            AuditScope::forSchool($school),
            new AuditEventData(
                action: $action,
                resourceType: $resourceType,
                resourceId: $resourceId,
                category: 'school_configuration',
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
    private function educationalLevelAuditSnapshot(EducationalLevel $level): array
    {
        return [
            'name' => (string) $level->name,
            'code' => $level->code === null ? null : (string) $level->code,
            'display_order' => (int) $level->display_order,
            'is_active' => (bool) $level->is_active,
            'configuration_present' => is_array($level->configuration) && $level->configuration !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gradeLevelAuditSnapshot(GradeLevel $grade): array
    {
        return [
            'educational_level_id' => (int) $grade->educational_level_id,
            'name' => (string) $grade->name,
            'code' => $grade->code === null ? null : (string) $grade->code,
            'display_order' => (int) $grade->display_order,
            'is_active' => (bool) $grade->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function classDefinitionAuditSnapshot(ClassDefinition $class): array
    {
        return [
            'grade_level_id' => (int) $class->grade_level_id,
            'name' => (string) $class->name,
            'code' => $class->code === null ? null : (string) $class->code,
            'display_order' => (int) $class->display_order,
            'is_active' => (bool) $class->is_active,
            'configuration_present' => is_array($class->configuration) && $class->configuration !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function streamAuditSnapshot(Stream $stream): array
    {
        return [
            'class_definition_id' => (int) $stream->class_definition_id,
            'name' => (string) $stream->name,
            'code' => $stream->code === null ? null : (string) $stream->code,
            'display_order' => (int) $stream->display_order,
            'is_active' => (bool) $stream->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function classOfferingAuditSnapshot(ClassOffering $offering): array
    {
        return [
            'class_definition_id' => (int) $offering->class_definition_id,
            'academic_year_id' => (int) $offering->academic_year_id,
            'name' => $offering->name === null ? null : (string) $offering->name,
            'is_active' => (bool) $offering->is_active,
            'configuration_present' => is_array($offering->configuration) && $offering->configuration !== [],
        ];
    }
}
