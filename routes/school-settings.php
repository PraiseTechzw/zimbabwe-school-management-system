<?php

declare(strict_types=1);

use App\Http\Controllers\SchoolSettingsController;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Support\Facades\Route;

Route::get('/school-settings', [SchoolSettingsController::class, 'show'])
    ->middleware(EnsurePermission::class.':school_settings.view');
Route::match(['put', 'patch'], '/school-settings', [SchoolSettingsController::class, 'update'])
    ->middleware(EnsurePermission::class.':school_settings.manage');
Route::get('/school-settings/revisions', [SchoolSettingsController::class, 'revisions'])
    ->middleware(EnsurePermission::class.':school_settings.view');
