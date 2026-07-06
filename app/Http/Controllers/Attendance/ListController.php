<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\ListService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListController extends Controller
{
    public function __construct(private ListService $listService) {}

    public function index(Request $request): View
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();
        $attendanceRows = $this->listService->createMonthlyRows($request->user(), $month);
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
