<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsvPipelineController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('/v1')->group(function () {
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::patch('/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);
    Route::patch('/activities/{activity}/start', [ActivityController::class, 'start']);
    Route::patch('/activities/{activity}/complete', [ActivityController::class, 'complete']);

    Route::get('/notifications', [NotificationController::class, 'index']);

    Route::post('/csv-import', [CsvPipelineController::class, 'processCsv']);
});
