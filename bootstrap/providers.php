<?php

use App\Providers\AcademicCalendarServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\SchoolSettingsServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AcademicCalendarServiceProvider::class,
    SchoolSettingsServiceProvider::class,
];
