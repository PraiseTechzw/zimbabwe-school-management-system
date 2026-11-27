<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicCalendar\Models\AcademicPeriod;
use App\Domains\AcademicCalendar\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Period '.fake()->unique()->numberBetween(1, 100000),
            'type' => 'term',
            'sequence' => fake()->unique()->numberBetween(1, 100000),
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'published',
            'counting_basis' => 'school_days',
            'source' => 'School calendar register',
            'source_reference' => null,
            'source_published_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (AcademicPeriod $period): void {
            if ($period->school_id === null && $period->academicYear !== null) {
                $period->school_id = $period->academicYear->school_id;
            }
        });
    }

    /**
     * Bind the period to a specific year and inherit that year's school.
     */
    public function inYear(AcademicYear $year): static
    {
        return $this->state(fn (array $attributes): array => [
            'academic_year_id' => $year->id,
            'school_id' => $year->school_id,
        ]);
    }
}
