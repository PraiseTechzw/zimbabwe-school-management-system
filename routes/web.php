<?php

use App\Http\Controllers\SchoolContextController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'school.context'])->prefix('api')->group(function (): void {
    Route::get('/school', [SchoolContextController::class, 'current']);
    Route::get('/school/identifiers', [SchoolContextController::class, 'identifiers']);
});
