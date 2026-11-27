<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationalLevel>
 */
class EducationalLevelFactory extends Factory
{
    protected $model = EducationalLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => 'Level '.fake()->unique()->numberBetween(1, 5000),
            'code' => null,
            'display_order' => fake()->unique()->numberBetween(1, 5000),
            'is_active' => true,
            'configuration' => null,
        ];
    }
}
