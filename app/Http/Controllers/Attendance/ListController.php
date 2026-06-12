<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request)
    {
        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();
        $attendanceRows = $this->attendanceService->createMonthlyRows($request->user(), $month);
        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return view('attendance.list', compact(
            'month',
            'attendanceRows',
            'previousMonth',
            'nextMonth'
        ));
    }
}
