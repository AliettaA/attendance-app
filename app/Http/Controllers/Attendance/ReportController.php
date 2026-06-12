<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\AttendanceReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private AttendanceReportService $attendanceReportService) {}

    public function index(Request $request)
    {
        $report = $this->attendanceReportService->build($request->user());

        return view('attendance.report', compact('report'));
    }
}
