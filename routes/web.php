<?php

declare(strict_types=1);

use App\Http\Controllers\Frontend\AcademicCalendarViewController;
use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Frontend\ModuleNoticeController;
use App\Http\Controllers\Frontend\SchoolProfileController;
use App\Http\Controllers\SchoolContextController;
use App\Livewire\CalendarAdministration;
use App\Livewire\Dashboard;
use App\Livewire\RoleAssignments;
use App\Livewire\SchoolSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', [LoginController::class, 'show'])
    ->middleware('guest')
    ->name('login');

Route::middleware(['auth', 'school.context'])->prefix('app')->group(function (): void {
    Route::get('/', Dashboard::class)->name('frontend.dashboard');
    Route::get('/school', [SchoolProfileController::class, 'index'])->name('frontend.school-profile');
    Route::get('/academic-calendar', [AcademicCalendarViewController::class, 'index'])->name('frontend.academic-calendar');
    Route::get('/academic-calendar/manage', CalendarAdministration::class)->name('frontend.calendar-manage');
    Route::get('/school-settings', SchoolSettings::class)->name('frontend.school-settings');
    Route::get('/roles', RoleAssignments::class)->name('frontend.roles');
    Route::get('/modules/{module}', [ModuleNoticeController::class, 'show'])
        ->where('module', '[a-z0-9-]+')
        ->name('frontend.module');
});

Route::middleware(['auth', 'school.context'])->prefix('api')->group(function (): void {
    Route::get('/school', [SchoolContextController::class, 'current']);
    Route::get('/school/identifiers', [SchoolContextController::class, 'identifiers']);
});
