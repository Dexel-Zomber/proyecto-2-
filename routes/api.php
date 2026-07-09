<?php

use App\Http\Controllers\Api\AcademicApiController;
use App\Http\Controllers\Api\ReportApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/courses', [AcademicApiController::class, 'courses']);
    Route::get('/subjects', [AcademicApiController::class, 'subjects']);
    Route::get('/students/{student}/scores', [AcademicApiController::class, 'studentScores']);
    Route::get('/students/{student}/alerts', [AcademicApiController::class, 'studentAlerts']);

    Route::get('/reports/courses/{course}', [ReportApiController::class, 'course']);
    Route::get('/reports/teachers/{teacher}', [ReportApiController::class, 'teacher']);
    Route::get('/reports/students/{student}', [ReportApiController::class, 'student']);
});
