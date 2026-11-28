<?php

use App\Providers\AcademicCalendarServiceProvider;
use App\Providers\AcademicStructureServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\LearnerManagementServiceProvider;
use App\Providers\RolesServiceProvider;
use App\Providers\SchoolSettingsServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AcademicCalendarServiceProvider::class,
    AcademicStructureServiceProvider::class,
    LearnerManagementServiceProvider::class,
    SchoolSettingsServiceProvider::class,
    RolesServiceProvider::class,
];
