<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolIdentifier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolIdentifier>
 */
class SchoolIdentifierFactory extends Factory
{
    protected $model = SchoolIdentifier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = fake()->unique()->bothify('???-###');

        return [
            'school_id' => School::factory(),
            'type' => 'emis',
            'value' => $value,
            'normalized_value' => mb_strtolower($value),
            'source' => 'MoPSE register',
            'verification_status' => 'proposed',
            'valid_from' => null,
            'valid_to' => null,
        ];
    }
}
