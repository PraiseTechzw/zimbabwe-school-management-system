<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use Illuminate\Support\Facades\Route;

Route::post('/api/auth/login', [LoginController::class, 'store'])
    ->middleware(['throttle:login', 'throttle:login-ip'])
    ->name('auth.login.store');

Route::post('/api/auth/logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.logout.destroy');

Route::get('/api/auth/me', [MeController::class, 'show'])
    ->middleware('auth')
    ->name('auth.me.show');
