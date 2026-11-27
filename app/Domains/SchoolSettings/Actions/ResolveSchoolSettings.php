<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Actions;

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSetting;

/**
 * Resolves the school's settings without writing default data on reads.
 */
final class ResolveSchoolSettings
{
    public function execute(School $school): SchoolSetting
    {
        /** @var SchoolSetting|null $setting */
        $setting = SchoolSetting::query()->forSchool($school)->first();

        return $setting ?? SchoolSetting::defaultFor($school);
    }
}
