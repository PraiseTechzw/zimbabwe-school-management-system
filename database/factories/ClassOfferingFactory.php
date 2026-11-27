<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassOffering>
 */
class ClassOfferingFactory extends Factory
{
    protected $model = ClassOffering::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'class_definition_id' => ClassDefinition::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name' => null,
            'is_active' => true,
            'configuration' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ClassOffering $offering): void {
            $class = $offering->classDefinition;

            if ($class === null) {
                return;
            }

            $offering->school_id = $class->school_id;
            $year = $offering->academicYear;

            if ($year === null || (int) $year->school_id !== (int) $class->school_id) {
                /** @var School $school */
                $school = School::query()->findOrFail($class->school_id);
                $offering->academic_year_id = AcademicYear::factory()->for($school)->create()->id;
            }
        });
    }

    public function forClassAndYear(ClassDefinition $class, AcademicYear $year): static
    {
        return $this->state(fn (): array => [
            'school_id' => $class->school_id,
            'class_definition_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);
    }
}
