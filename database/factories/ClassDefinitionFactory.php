<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassDefinition>
 */
class ClassDefinitionFactory extends Factory
{
    protected $model = ClassDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'name' => 'Class '.fake()->unique()->numberBetween(1, 5000),
            'code' => null,
            'display_order' => fake()->unique()->numberBetween(1, 5000),
            'is_active' => true,
            'configuration' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ClassDefinition $class): void {
            if ($class->gradeLevel !== null) {
                $class->school_id = $class->gradeLevel->school_id;
            }
        });
    }

    public function inGrade(GradeLevel $grade): static
    {
        return $this->state(fn (): array => [
            'school_id' => $grade->school_id,
            'grade_level_id' => $grade->id,
        ]);
    }
}
