<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private AttendanceReportService $attendanceReportService) {}

    public function index(Request $request): View
    {
        $report = $this->attendanceReportService->build($request->user());

        return view('attendance.report', compact('report'));
    }
}
