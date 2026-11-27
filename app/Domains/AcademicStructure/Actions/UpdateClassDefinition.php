<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Actions;

use App\Domains\AcademicStructure\Concerns\GuardsAcademicStructure;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateClassDefinition
{
    use GuardsAcademicStructure;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, ClassDefinition $class, array $data): ClassDefinition
    {
        $validated = Validator::make(array_merge([
            'grade_level_id' => $class->grade_level_id,
            'name' => $class->name,
            'code' => $class->code,
            'display_order' => $class->display_order,
            'is_active' => $class->is_active,
            'configuration' => $class->configuration,
        ], $data), [
            'grade_level_id' => ['required', 'integer', 'min:1'],
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
        $active = $validated['is_active'] === null ? (bool) $class->is_active : (bool) $validated['is_active'];

        return DB::transaction(function () use ($school, $class, $validated, $name, $code, $order, $configuration, $active): ClassDefinition {
            $this->lockStructureForSchool($school);
            $this->assertOwnedBySchool($school, $class);
            $grade = $this->resolveGradeLevel($school, (int) $validated['grade_level_id']);

            $this->assertUniqueLabel(
                ClassDefinition::class,
                'name',
                $name,
                ['school_id' => $school->id, 'grade_level_id' => $grade->id],
                'name',
                'A class definition with this name already exists in the selected grade level.',
                (int) $class->id,
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    ClassDefinition::class,
                    'code',
                    $code,
                    ['school_id' => $school->id, 'grade_level_id' => $grade->id],
                    'code',
                    'This code is already used in the selected grade level.',
                    (int) $class->id,
                );
            }

            $this->assertNoDuplicate(
                ClassDefinition::class,
                [
                    'school_id' => $school->id,
                    'grade_level_id' => $grade->id,
                    'display_order' => $order,
                ],
                'display_order',
                'This display order is already used in the selected grade level.',
                (int) $class->id,
            );

            $class->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
                'configuration' => $configuration,
            ]);
            $class->grade_level_id = $grade->id;
            $class->save();

            return $class;
        });
    }
}
