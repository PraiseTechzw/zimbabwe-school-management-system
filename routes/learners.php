<?php

use App\Http\Controllers\LearnerController;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Support\Facades\Route;

Route::get('/learners', [LearnerController::class, 'index'])
    ->middleware(EnsurePermission::class.':learners.view');
Route::post('/learners', [LearnerController::class, 'store'])
    ->middleware(EnsurePermission::class.':learners.manage');
Route::get('/learners/{learner}', [LearnerController::class, 'show'])
    ->whereNumber('learner')
    ->middleware(EnsurePermission::class.':learners.view');
Route::match(['put', 'patch'], '/learners/{learner}', [LearnerController::class, 'update'])
    ->whereNumber('learner')
    ->middleware(EnsurePermission::class.':learners.manage');
