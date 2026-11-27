<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    protected $model = GradeLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'educational_level_id' => EducationalLevel::factory(),
            'name' => 'Grade '.fake()->unique()->numberBetween(1, 5000),
            'code' => null,
            'display_order' => fake()->unique()->numberBetween(1, 5000),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (GradeLevel $grade): void {
            if ($grade->educationalLevel !== null) {
                $grade->school_id = $grade->educationalLevel->school_id;
            }
        });
    }

    public function inLevel(EducationalLevel $level): static
    {
        return $this->state(fn (): array => [
            'school_id' => $level->school_id,
            'educational_level_id' => $level->id,
        ]);
    }
}
