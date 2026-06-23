<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->only(['index', 'show'])
        ->parameters([
            'attendance-records' => 'attendanceRecord',
        ]);

    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('auth:sanctum')
        ->parameters([
            'attendance-records' => 'attendanceRecord',
        ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
