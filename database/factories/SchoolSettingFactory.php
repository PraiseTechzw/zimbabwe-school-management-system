<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolSetting>
 */
class SchoolSettingFactory extends Factory
{
    protected $model = SchoolSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'locale' => 'en',
            'timezone' => 'UTC',
            'source' => 'School configuration register',
            'effective_from' => now()->toDateString(),
            'version' => 1,
        ];
    }

    public function forSchool(School $school): static
    {
        return $this->state(fn (): array => ['school_id' => $school->id]);
    }
}
