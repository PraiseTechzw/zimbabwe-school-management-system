<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Actions;

use App\Domains\AcademicStructure\Concerns\GuardsAcademicStructure;
use App\Domains\AcademicStructure\Concerns\RecordsAcademicStructureAudit;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateClassOffering
{
    use GuardsAcademicStructure;
    use RecordsAcademicStructureAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, ClassOffering $offering, array $data): ClassOffering
    {
        $validated = Validator::make(array_merge([
            'class_definition_id' => $offering->class_definition_id,
            'academic_year_id' => $offering->academic_year_id,
            'name' => $offering->name,
            'is_active' => $offering->is_active,
            'configuration' => $offering->configuration,
        ], $data), [
            'class_definition_id' => ['required', 'integer', 'min:1'],
            'academic_year_id' => ['required', 'integer', 'min:1'],
            'name' => array_merge(['nullable'], array_slice(AcademicStructureRules::name(), 1)),
            'is_active' => array_merge(['nullable'], AcademicStructureRules::active()),
            'configuration' => AcademicStructureRules::configuration(),
        ])->validate();

        $name = isset($validated['name']) ? trim((string) $validated['name']) : null;
        $name = $name === '' || $name === null ? null : $name;
        $configuration = $validated['configuration'] ?? null;
        $configuration = is_array($configuration) && $configuration !== [] ? $configuration : null;
        $active = $validated['is_active'] === null ? (bool) $offering->is_active : (bool) $validated['is_active'];

        return DB::transaction(function () use ($school, $offering, $validated, $name, $configuration, $active): ClassOffering {
            $this->lockStructureForSchool($school);
            $this->assertOwnedBySchool($school, $offering);
            $class = $this->resolveClassDefinition(
                $school,
                (int) $validated['class_definition_id'],
                (bool) config('academic-structure.allow_inactive_class_offering', false),
            );
            $year = $this->resolveAcademicYear($school, (int) $validated['academic_year_id']);

            $this->assertNoDuplicate(
                ClassOffering::class,
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'class_definition_id' => $class->id,
                ],
                'class_definition_id',
                'This class is already offered in the selected academic year.',
                (int) $offering->id,
            );

            if ($name !== null) {
                $this->assertUniqueLabel(
                    ClassOffering::class,
                    'name',
                    $name,
                    ['school_id' => $school->id, 'academic_year_id' => $year->id],
                    'name',
                    'An offering with this name already exists in the selected academic year.',
                    (int) $offering->id,
                );
            }

            $before = $this->classOfferingAuditSnapshot($offering);

            $offering->fill([
                'name' => $name,
                'is_active' => $active,
                'configuration' => $configuration,
            ]);
            $offering->class_definition_id = $class->id;
            $offering->academic_year_id = $year->id;
            $offering->save();

            $this->recordStructureAudit(
                $school,
                'class_offering.updated',
                'class_offering',
                (string) $offering->id,
                $before,
                $this->classOfferingAuditSnapshot($offering),
            );

            return $offering;
        });
    }
}
