<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Actions;

use App\Domains\AcademicStructure\Concerns\GuardsAcademicStructure;
use App\Domains\AcademicStructure\Concerns\RecordsAcademicStructureAudit;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateGradeLevel
{
    use GuardsAcademicStructure;
    use RecordsAcademicStructureAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, GradeLevel $grade, array $data): GradeLevel
    {
        $validated = Validator::make(array_merge([
            'educational_level_id' => $grade->educational_level_id,
            'name' => $grade->name,
            'code' => $grade->code,
            'display_order' => $grade->display_order,
            'is_active' => $grade->is_active,
        ], $data), [
            'educational_level_id' => ['required', 'integer', 'min:1'],
            'name' => AcademicStructureRules::name(),
            'code' => AcademicStructureRules::code(),
            'display_order' => AcademicStructureRules::displayOrder(),
            'is_active' => array_merge(['nullable'], AcademicStructureRules::active()),
        ])->validate();

        $name = trim((string) $validated['name']);
        $code = isset($validated['code']) ? trim((string) $validated['code']) : null;
        $code = $code === '' ? null : $code;
        $order = (int) $validated['display_order'];
        $active = $validated['is_active'] === null ? (bool) $grade->is_active : (bool) $validated['is_active'];

        return DB::transaction(function () use ($school, $grade, $validated, $name, $code, $order, $active): GradeLevel {
            $this->lockStructureForSchool($school);
            $this->assertOwnedBySchool($school, $grade);
            $level = $this->resolveEducationalLevel($school, (int) $validated['educational_level_id']);

            $this->assertUniqueLabel(
                GradeLevel::class,
                'name',
                $name,
                ['school_id' => $school->id],
                'name',
                'A grade level with this name is already configured for this school.',
                (int) $grade->id,
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    GradeLevel::class,
                    'code',
                    $code,
                    ['school_id' => $school->id],
                    'code',
                    'This code is already used by another grade level in this school.',
                    (int) $grade->id,
                );
            }

            $this->assertNoDuplicate(
                GradeLevel::class,
                [
                    'school_id' => $school->id,
                    'educational_level_id' => $level->id,
                    'display_order' => $order,
                ],
                'display_order',
                'This display order is already used in the selected educational level.',
                (int) $grade->id,
            );

            $before = $this->gradeLevelAuditSnapshot($grade);

            $grade->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
            ]);
            $grade->educational_level_id = $level->id;
            $grade->save();

            $this->recordStructureAudit(
                $school,
                'grade_level.updated',
                'grade_level',
                (string) $grade->id,
                $before,
                $this->gradeLevelAuditSnapshot($grade),
            );

            return $grade;
        });
    }
}
