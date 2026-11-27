<?php

declare(strict_types=1);

use App\Http\Controllers\SchoolSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/school-settings', [SchoolSettingsController::class, 'show']);
Route::match(['put', 'patch'], '/school-settings', [SchoolSettingsController::class, 'update']);
Route::get('/school-settings/revisions', [SchoolSettingsController::class, 'revisions']);
