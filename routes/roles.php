<?php

declare(strict_types=1);

use App\Http\Controllers\RoleAssignmentController;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Support\Facades\Route;

Route::get('/role-assignments', [RoleAssignmentController::class, 'index'])
    ->middleware(EnsurePermission::class.':roles.view');

Route::post('/role-assignments', [RoleAssignmentController::class, 'store'])
    ->middleware(EnsurePermission::class.':roles.manage');

Route::delete('/role-assignments/{roleAssignment}', [RoleAssignmentController::class, 'destroy'])
    ->whereNumber('roleAssignment')
    ->middleware(EnsurePermission::class.':roles.manage');
