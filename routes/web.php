<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\CorrectionRequestController;

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart']);
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::get('/attendance/report', [AttendanceReportController::class, 'index']);
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show']);
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'storeCorrectionRequest']);
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', function () {
        return '<p>管理者：勤怠一覧画面</p>
            <form method="POST" action="/logout">
                ' . csrf_field() . '
                <button type="submit">ログアウト</button>
            </form>
        ';
    });
});

Route::middleware('auth')->get('/home', function () {
    if (auth()->user()->role === 'admin') {
        return redirect('/admin/attendance/list');
    }
    return redirect('/attendance');
});