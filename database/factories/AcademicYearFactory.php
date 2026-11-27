<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2020, 2040);

        return [
            'school_id' => School::factory(),
            'name' => (string) $year,
            'start_date' => $year.'-01-01',
            'end_date' => $year.'-12-31',
            'status' => 'published',
            'source' => 'School calendar register',
            'source_reference' => null,
            'source_published_at' => null,
        ];
    }
}
