<?php

use App\Http\Controllers\AcademicCalendarController;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Support\Facades\Route;

Route::get('/academic-years/current', [AcademicCalendarController::class, 'current']);
Route::get('/academic-years', [AcademicCalendarController::class, 'years']);
Route::post('/academic-years', [AcademicCalendarController::class, 'storeYear'])
    ->middleware(EnsurePermission::class.':academic_calendar.manage');
Route::match(['put', 'patch'], '/academic-years/{academicYear}', [AcademicCalendarController::class, 'updateYear'])
    ->whereNumber('academicYear')
    ->middleware(EnsurePermission::class.':academic_calendar.manage');
Route::get('/academic-years/{academicYear}/periods', [AcademicCalendarController::class, 'periods'])->whereNumber('academicYear');
Route::post('/academic-years/{academicYear}/periods', [AcademicCalendarController::class, 'storePeriod'])
    ->whereNumber('academicYear')
    ->middleware(EnsurePermission::class.':academic_calendar.manage');
Route::match(['put', 'patch'], '/academic-periods/{academicPeriod}', [AcademicCalendarController::class, 'updatePeriod'])
    ->whereNumber('academicPeriod')
    ->middleware(EnsurePermission::class.':academic_calendar.manage');
Route::get('/academic-calendar/validation', [AcademicCalendarController::class, 'validateCalendar']);
