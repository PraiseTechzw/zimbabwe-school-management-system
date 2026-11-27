<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use App\Domains\SchoolSettings\Models\SchoolSettingRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolSettingRevision>
 */
class SchoolSettingRevisionFactory extends Factory
{
    protected $model = SchoolSettingRevision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'school_setting_id' => SchoolSetting::factory(),
            'version' => 1,
            'values' => ['locale' => 'en', 'timezone' => 'UTC'],
            'source' => 'School configuration register',
            'effective_from' => now()->toDateString(),
        ];
    }

    public function forSetting(SchoolSetting $setting): static
    {
        return $this->state(fn (): array => [
            'school_id' => $setting->school_id,
            'school_setting_id' => $setting->id,
        ]);
    }
}
