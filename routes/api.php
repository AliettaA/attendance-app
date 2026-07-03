<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->only(['index', 'show'])
        ->parameters([
            'attendance-records' => 'attendanceRecord',
        ]);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('attendance-records', AttendanceRecordController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters([
                'attendance-records' => 'attendanceRecord',
            ]);
    });
});
