<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' School',
            'type' => 'government',
            'ward' => fake()->word(),
            'district' => fake()->city(),
            'province' => fake()->city(),
        ];
    }
}
