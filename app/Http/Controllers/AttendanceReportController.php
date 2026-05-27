<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReportService;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function __construct(private AttendanceReportService $attendanceReportService)
    {
    }

    public function index(Request $request)
    {
        $report = $this->attendanceReportService->build($request->user());

        return view('attendance.report', compact('report'));
    }
}
