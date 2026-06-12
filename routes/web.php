<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Attendance\DetailController;
use App\Http\Controllers\Attendance\ListController;
use App\Http\Controllers\Attendance\ReportController;
use App\Http\Controllers\Attendance\StampController;
use App\Http\Controllers\CorrectionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.attendance.index');
    }

    return redirect()->route('attendance.index');
})->name('root');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [StampController::class, 'index'])->name('index');
        Route::post('/clock-in', [StampController::class, 'clockIn'])->name('clock_in');
        Route::post('/clock-out', [StampController::class, 'clockOut'])->name('clock_out');
        Route::post('/break-start', [StampController::class, 'breakStart'])->name('break_start');
        Route::post('/break-end', [StampController::class, 'breakEnd'])->name('break_end');
        Route::get('/list', [ListController::class, 'index'])->name('list');
        Route::get('/report', [ReportController::class, 'index'])->name('report');
        Route::prefix('detail')->name('detail.')->group(function () {
            Route::get('/create', [DetailController::class, 'createByDate'])->name('create');
            Route::post('/create', [DetailController::class, 'storeCorrectionRequestByDate'])->name('store_by_date');
            Route::get('/{id}', [DetailController::class, 'show'])->name('show');
            Route::post('/{id}', [DetailController::class, 'storeCorrectionRequest'])->name('request');
        });
    });

    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])->name('correction_requests.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/list', [AdminAttendanceController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminAttendanceController::class, 'show'])->name('show');
        Route::post('/{id}', [AdminAttendanceController::class, 'update'])->name('update');
        Route::get('/staff/{id}/detail/create', [AdminAttendanceController::class, 'createForStaffDate'])->name('staff.detail.create');
        Route::post('/staff/{id}/detail/create', [AdminAttendanceController::class, 'storeForStaffDate'])->name('staff.detail.store');
        Route::get('/staff/{id}/csv', [AdminAttendanceController::class, 'exportStaffCsv'])->name('staff.csv');
        Route::get('/staff/{id}', [AdminAttendanceController::class, 'staff'])->name('staff');
    });

    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.index');
});

Route::middleware(['auth', 'verified', 'admin'])->name('admin.')->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'show'])->name('correction_requests.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'approve'])->name('correction_requests.approve');
});

Route::middleware('guest')->get('/admin/login', function () {
    return view('auth.admin_login');
})->name('admin.login');

Route::middleware(['auth', 'verified'])->get('/home', function () {
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.attendance.index');
    }

    return redirect()->route('attendance.index');
})->name('home');
