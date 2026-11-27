<?php

use App\Providers\AcademicCalendarServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\RolesServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AcademicCalendarServiceProvider::class,
    RolesServiceProvider::class,
];
