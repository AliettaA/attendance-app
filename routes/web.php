<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\CorrectionRequestController;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect('/login');
    }

    if (auth()->user()->role === 'admin') {
        return redirect('/admin/attendance/list');
    }

    return redirect('/attendance');
});

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
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show']);
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update']);
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index']);
    Route::get('/admin/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportStaffCsv']);
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'show']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'approve']);
});

Route::middleware('guest')->get('/admin/login', function () {
    return view('auth.admin_login');
});

Route::middleware('auth')->get('/home', function () {
    if (auth()->user()->role === 'admin') {
        return redirect('/admin/attendance/list');
    }
    return redirect('/attendance');
});
