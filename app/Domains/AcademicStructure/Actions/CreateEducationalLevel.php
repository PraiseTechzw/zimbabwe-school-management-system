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

final class CreateEducationalLevel
{
    use GuardsAcademicStructure;
    use RecordsAcademicStructureAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): EducationalLevel
    {
        $validated = Validator::make($data, [
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
        $active = (bool) ($validated['is_active'] ?? config('academic-structure.default_is_active', true));

        return DB::transaction(function () use ($school, $name, $code, $order, $configuration, $active): EducationalLevel {
            $this->lockStructureForSchool($school);
            $this->assertUniqueLabel(
                EducationalLevel::class,
                'name',
                $name,
                ['school_id' => $school->id],
                'name',
                'An educational level with this name is already configured for this school.',
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    EducationalLevel::class,
                    'code',
                    $code,
                    ['school_id' => $school->id],
                    'code',
                    'This code is already used by another educational level in this school.',
                );
            }

            $this->assertNoDuplicate(
                EducationalLevel::class,
                ['school_id' => $school->id, 'display_order' => $order],
                'display_order',
                'This display order is already used by another educational level in this school.',
            );

            $level = new EducationalLevel;
            $level->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
                'configuration' => $configuration,
            ]);
            $level->school_id = $school->id;
            $level->save();

            $this->recordStructureAudit(
                $school,
                'educational_level.created',
                'educational_level',
                (string) $level->id,
                null,
                $this->educationalLevelAuditSnapshot($level),
            );

            return $level;
        });
    }
}
