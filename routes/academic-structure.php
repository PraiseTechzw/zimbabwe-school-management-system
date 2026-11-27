<?php

use App\Http\Controllers\AcademicStructureController;
use Illuminate\Support\Facades\Route;

Route::get('/academic-structure/levels', [AcademicStructureController::class, 'levels']);
Route::post('/academic-structure/levels', [AcademicStructureController::class, 'storeLevel']);
Route::match(['put', 'patch'], '/academic-structure/levels/{educationalLevel}', [AcademicStructureController::class, 'updateLevel'])->whereNumber('educationalLevel');

Route::get('/academic-structure/grade-levels', [AcademicStructureController::class, 'allGradeLevels']);
Route::get('/academic-structure/levels/{educationalLevel}/grade-levels', [AcademicStructureController::class, 'gradeLevels'])->whereNumber('educationalLevel');
Route::post('/academic-structure/levels/{educationalLevel}/grade-levels', [AcademicStructureController::class, 'storeGrade'])->whereNumber('educationalLevel');
Route::match(['put', 'patch'], '/academic-structure/grade-levels/{gradeLevel}', [AcademicStructureController::class, 'updateGrade'])->whereNumber('gradeLevel');

Route::get('/academic-structure/grade-levels/{gradeLevel}/classes', [AcademicStructureController::class, 'classes'])->whereNumber('gradeLevel');
Route::post('/academic-structure/grade-levels/{gradeLevel}/classes', [AcademicStructureController::class, 'storeClass'])->whereNumber('gradeLevel');
Route::match(['put', 'patch'], '/academic-structure/classes/{classDefinition}', [AcademicStructureController::class, 'updateClass'])->whereNumber('classDefinition');

Route::get('/academic-structure/classes/{classDefinition}/streams', [AcademicStructureController::class, 'streams'])->whereNumber('classDefinition');
Route::post('/academic-structure/classes/{classDefinition}/streams', [AcademicStructureController::class, 'storeStream'])->whereNumber('classDefinition');
Route::match(['put', 'patch'], '/academic-structure/streams/{stream}', [AcademicStructureController::class, 'updateStream'])->whereNumber('stream');

Route::get('/academic-structure/class-offerings', [AcademicStructureController::class, 'offerings']);
Route::post('/academic-structure/class-offerings', [AcademicStructureController::class, 'storeOffering']);
Route::match(['put', 'patch'], '/academic-structure/class-offerings/{classOffering}', [AcademicStructureController::class, 'updateOffering'])->whereNumber('classOffering');
