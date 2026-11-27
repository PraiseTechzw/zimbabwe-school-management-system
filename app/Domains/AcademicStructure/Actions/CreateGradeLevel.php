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

final class CreateGradeLevel
{
    use GuardsAcademicStructure;
    use RecordsAcademicStructureAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): GradeLevel
    {
        $validated = Validator::make($data, [
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
        $active = (bool) ($validated['is_active'] ?? config('academic-structure.default_is_active', true));

        return DB::transaction(function () use ($school, $validated, $name, $code, $order, $active): GradeLevel {
            $this->lockStructureForSchool($school);
            $level = $this->resolveEducationalLevel($school, (int) $validated['educational_level_id']);
            $this->assertUniqueLabel(
                GradeLevel::class,
                'name',
                $name,
                ['school_id' => $school->id],
                'name',
                'A grade level with this name is already configured for this school.',
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    GradeLevel::class,
                    'code',
                    $code,
                    ['school_id' => $school->id],
                    'code',
                    'This code is already used by another grade level in this school.',
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
            );

            $grade = new GradeLevel;
            $grade->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
            ]);
            $grade->school_id = $school->id;
            $grade->educational_level_id = $level->id;
            $grade->save();

            $this->recordStructureAudit(
                $school,
                'grade_level.created',
                'grade_level',
                (string) $grade->id,
                null,
                $this->gradeLevelAuditSnapshot($grade),
            );

            return $grade;
        });
    }
}
