<?php

use App\Http\Controllers\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/academic-years/current', [AcademicCalendarController::class, 'current']);
Route::get('/academic-years', [AcademicCalendarController::class, 'years']);
Route::post('/academic-years', [AcademicCalendarController::class, 'storeYear']);
Route::match(['put', 'patch'], '/academic-years/{academicYear}', [AcademicCalendarController::class, 'updateYear'])->whereNumber('academicYear');
Route::get('/academic-years/{academicYear}/periods', [AcademicCalendarController::class, 'periods'])->whereNumber('academicYear');
Route::post('/academic-years/{academicYear}/periods', [AcademicCalendarController::class, 'storePeriod'])->whereNumber('academicYear');
Route::match(['put', 'patch'], '/academic-periods/{academicPeriod}', [AcademicCalendarController::class, 'updatePeriod'])->whereNumber('academicPeriod');
Route::get('/academic-calendar/validation', [AcademicCalendarController::class, 'validateCalendar']);
