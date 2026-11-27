<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolStatus>
 */
class SchoolStatusFactory extends Factory
{
    protected $model = SchoolStatus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'status' => 'registered',
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'source' => 'Education Act registration',
        ];
    }
}
