<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class StampController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());

        return view('attendance.index', compact('attendance'));
    }

    public function clockIn(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if ($attendance) {
            return redirect()->route('attendance.index');
        }
        $this->attendanceService->clockIn($request->user());

        return redirect()->route('attendance.index');
    }

    public function clockOut(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if (! $attendance || $attendance->status !== 'working') {
            return redirect()->route('attendance.index');
        }
        $this->attendanceService->clockOut($attendance);

        return redirect()->route('attendance.index');
    }

    public function breakStart(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if (! $attendance || $attendance->status !== 'working') {
            return redirect()->route('attendance.index');
        }
        $this->attendanceService->breakStart($attendance);

        return redirect()->route('attendance.index');
    }

    public function breakEnd(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if (! $attendance || $attendance->status !== 'on_break') {
            return redirect()->route('attendance.index');
        }
        $this->attendanceService->breakEnd($attendance);

        return redirect()->route('attendance.index');
    }
}
