<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Learner>
 */
class LearnerFactory extends Factory
{
    protected $model = Learner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'learner_reference' => 'LRN-'.Str::upper((string) Str::ulid()),
            'full_name' => fake()->name(),
            'date_of_birth' => null,
            'status' => 'registered',
            'class_offering_id' => null,
        ];
    }
}
