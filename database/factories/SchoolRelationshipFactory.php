<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolRelationship>
 */
class SchoolRelationshipFactory extends Factory
{
    protected $model = SchoolRelationship::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'related_school_id' => School::factory(),
            'relationship_type' => 'satellite_of',
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'evidence_reference' => 'MoPSE satellite school register',
        ];
    }
}
