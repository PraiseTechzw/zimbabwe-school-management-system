<?php

use App\Http\Controllers\AcademicStructureController;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Support\Facades\Route;

Route::get('/academic-structure/levels', [AcademicStructureController::class, 'levels'])
    ->middleware(EnsurePermission::class.':academic_structure.view');
Route::post('/academic-structure/levels', [AcademicStructureController::class, 'storeLevel'])
    ->middleware(EnsurePermission::class.':academic_structure.manage');
Route::match(['put', 'patch'], '/academic-structure/levels/{educationalLevel}', [AcademicStructureController::class, 'updateLevel'])
    ->whereNumber('educationalLevel')
    ->middleware(EnsurePermission::class.':academic_structure.manage');

Route::get('/academic-structure/grade-levels', [AcademicStructureController::class, 'allGradeLevels'])
    ->middleware(EnsurePermission::class.':academic_structure.view');
Route::get('/academic-structure/levels/{educationalLevel}/grade-levels', [AcademicStructureController::class, 'gradeLevels'])
    ->whereNumber('educationalLevel')
    ->middleware(EnsurePermission::class.':academic_structure.view');
Route::post('/academic-structure/levels/{educationalLevel}/grade-levels', [AcademicStructureController::class, 'storeGrade'])
    ->whereNumber('educationalLevel')
    ->middleware(EnsurePermission::class.':academic_structure.manage');
Route::match(['put', 'patch'], '/academic-structure/grade-levels/{gradeLevel}', [AcademicStructureController::class, 'updateGrade'])
    ->whereNumber('gradeLevel')
    ->middleware(EnsurePermission::class.':academic_structure.manage');

Route::get('/academic-structure/grade-levels/{gradeLevel}/classes', [AcademicStructureController::class, 'classes'])
    ->whereNumber('gradeLevel')
    ->middleware(EnsurePermission::class.':academic_structure.view');
Route::post('/academic-structure/grade-levels/{gradeLevel}/classes', [AcademicStructureController::class, 'storeClass'])
    ->whereNumber('gradeLevel')
    ->middleware(EnsurePermission::class.':academic_structure.manage');
Route::match(['put', 'patch'], '/academic-structure/classes/{classDefinition}', [AcademicStructureController::class, 'updateClass'])
    ->whereNumber('classDefinition')
    ->middleware(EnsurePermission::class.':academic_structure.manage');

Route::get('/academic-structure/classes/{classDefinition}/streams', [AcademicStructureController::class, 'streams'])
    ->whereNumber('classDefinition')
    ->middleware(EnsurePermission::class.':academic_structure.view');
Route::post('/academic-structure/classes/{classDefinition}/streams', [AcademicStructureController::class, 'storeStream'])
    ->whereNumber('classDefinition')
    ->middleware(EnsurePermission::class.':academic_structure.manage');
Route::match(['put', 'patch'], '/academic-structure/streams/{stream}', [AcademicStructureController::class, 'updateStream'])
    ->whereNumber('stream')
    ->middleware(EnsurePermission::class.':academic_structure.manage');

Route::get('/academic-structure/class-offerings', [AcademicStructureController::class, 'offerings'])
    ->middleware(EnsurePermission::class.':academic_structure.view');
Route::post('/academic-structure/class-offerings', [AcademicStructureController::class, 'storeOffering'])
    ->middleware(EnsurePermission::class.':academic_structure.manage');
Route::match(['put', 'patch'], '/academic-structure/class-offerings/{classOffering}', [AcademicStructureController::class, 'updateOffering'])
    ->whereNumber('classOffering')
    ->middleware(EnsurePermission::class.':academic_structure.manage');
