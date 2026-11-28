<?php

declare(strict_types=1);

use App\Http\Controllers\Frontend\AcademicCalendarViewController;
use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Frontend\ModuleNoticeController;
use App\Http\Controllers\Frontend\SchoolProfileController;
use App\Http\Controllers\SchoolContextController;
use App\Http\Controllers\TenantFileController;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureTenantSignedStorage;
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
    Route::get('/', Dashboard::class)
        ->middleware(EnsurePermission::class.':school.view')
        ->name('frontend.dashboard');
    Route::get('/school', [SchoolProfileController::class, 'index'])
        ->middleware(EnsurePermission::class.':school.view')
        ->name('frontend.school-profile');
    Route::get('/academic-calendar', [AcademicCalendarViewController::class, 'index'])
        ->middleware(EnsurePermission::class.':academic_calendar.view')
        ->name('frontend.academic-calendar');
    Route::get('/academic-calendar/manage', CalendarAdministration::class)
        ->middleware(EnsurePermission::class.':academic_calendar.manage')
        ->name('frontend.calendar-manage');
    Route::get('/school-settings', SchoolSettings::class)
        ->middleware(EnsurePermission::class.':school_settings.view')
        ->name('frontend.school-settings');
    Route::get('/roles', RoleAssignments::class)
        ->middleware(EnsurePermission::class.':roles.view')
        ->name('frontend.roles');
    Route::get('/modules/{module}', [ModuleNoticeController::class, 'show'])
        ->where('module', '[a-z0-9-]+')
        ->name('frontend.module');
});

Route::middleware(['auth', 'school.context'])->prefix('api')->group(function (): void {
    Route::get('/school', [SchoolContextController::class, 'current'])
        ->middleware(EnsurePermission::class.':school.view');
    Route::get('/school/identifiers', [SchoolContextController::class, 'identifiers'])
        ->middleware(EnsurePermission::class.':school_identifiers.view');
});

// Laravel's automatic `serve` routes are disabled for the private disk. These
// replacements require an authenticated school context and a signature bound
// to that school, so a signed URL cannot be replayed in another tenant.
Route::middleware(['auth', 'school.context', EnsureTenantSignedStorage::class])->group(function (): void {
    Route::get('/storage/{path}', [TenantFileController::class, 'show'])
        ->where('path', '.*')
        ->name('storage.local');
    Route::put('/storage/{path}', [TenantFileController::class, 'store'])
        ->where('path', '.*')
        ->name('storage.local.upload');
});
