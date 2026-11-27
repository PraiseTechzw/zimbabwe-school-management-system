<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Actions;

use App\Domains\AcademicStructure\Concerns\GuardsAcademicStructure;
use App\Domains\AcademicStructure\Concerns\RecordsAcademicStructureAudit;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateEducationalLevel
{
    use GuardsAcademicStructure;
    use RecordsAcademicStructureAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, EducationalLevel $level, array $data): EducationalLevel
    {
        $validated = Validator::make(array_merge([
            'name' => $level->name,
            'code' => $level->code,
            'display_order' => $level->display_order,
            'is_active' => $level->is_active,
            'configuration' => $level->configuration,
        ], $data), [
            'name' => AcademicStructureRules::name(),
            'code' => AcademicStructureRules::code(),
            'display_order' => AcademicStructureRules::displayOrder(),
            'is_active' => array_merge(['nullable'], AcademicStructureRules::active()),
            'configuration' => AcademicStructureRules::configuration(),
        ])->validate();

        $name = trim((string) $validated['name']);
        $code = isset($validated['code']) ? trim((string) $validated['code']) : null;
        $code = $code === '' ? null : $code;
        $order = (int) $validated['display_order'];
        $configuration = $validated['configuration'] ?? null;
        $configuration = is_array($configuration) && $configuration !== [] ? $configuration : null;
        $active = $validated['is_active'] === null ? (bool) $level->is_active : (bool) $validated['is_active'];

        return DB::transaction(function () use ($school, $level, $name, $code, $order, $configuration, $active): EducationalLevel {
            $this->lockStructureForSchool($school);
            $this->assertOwnedBySchool($school, $level);
            $this->assertUniqueLabel(
                EducationalLevel::class,
                'name',
                $name,
                ['school_id' => $school->id],
                'name',
                'An educational level with this name is already configured for this school.',
                (int) $level->id,
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    EducationalLevel::class,
                    'code',
                    $code,
                    ['school_id' => $school->id],
                    'code',
                    'This code is already used by another educational level in this school.',
                    (int) $level->id,
                );
            }

            $this->assertNoDuplicate(
                EducationalLevel::class,
                ['school_id' => $school->id, 'display_order' => $order],
                'display_order',
                'This display order is already used by another educational level in this school.',
                (int) $level->id,
            );

            $before = $this->educationalLevelAuditSnapshot($level);

            $level->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
                'configuration' => $configuration,
            ]);
            $level->save();

            $this->recordStructureAudit(
                $school,
                'educational_level.updated',
                'educational_level',
                (string) $level->id,
                $before,
                $this->educationalLevelAuditSnapshot($level),
            );

            return $level;
        });
    }
}
