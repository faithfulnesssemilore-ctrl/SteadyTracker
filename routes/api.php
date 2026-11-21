<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsvPipelineController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/health/ready', function () {
    $checks = [];

    try {
        DB::select('select 1');
        $checks['database'] = 'ok';
    } catch (Throwable $exception) {
        $checks['database'] = 'unavailable';
    }

    try {
        Redis::connection()->ping();
        $checks['redis'] = 'ok';
    } catch (Throwable $exception) {
        $checks['redis'] = 'unavailable';
    }

    $checks['mail'] = config('mail.default') && config('mail.from.address') ? 'configured' : 'missing';
    $checks['reverb'] = config('broadcasting.connections.reverb.key') ? 'configured' : 'missing';

    $healthy = $checks['database'] === 'ok'
        && $checks['redis'] === 'ok'
        && $checks['mail'] === 'configured'
        && $checks['reverb'] === 'configured';

    return response()->json([
        'status' => $healthy ? 'ready' : 'degraded',
        'checks' => $checks,
    ], $healthy ? 200 : 503);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::delete('/user', [AuthController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('/v1')->group(function () {
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::patch('/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);
    Route::patch('/activities/{activity}/start', [ActivityController::class, 'start']);
    Route::patch('/activities/{activity}/complete', [ActivityController::class, 'complete']);
    Route::get('/activities/{activity}/history', [ActivityController::class, 'history']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    Route::post('/csv-import', [CsvPipelineController::class, 'processCsv'])
        ->middleware('throttle:csv-import');
});
