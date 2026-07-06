<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\StampService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampController extends Controller
{
    public function __construct(private StampService $stampService) {}

    public function index(Request $request): View
    {
        $attendance = $this->stampService->getTodayAttendance($request->user());

        return view('attendance.index', compact('attendance'));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $attendance = $this->stampService->getTodayAttendance($request->user());
        if ($attendance) {
            return redirect()->route('attendance.index');
        }
        $this->stampService->clockIn($request->user());

        return redirect()->route('attendance.index');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $attendance = $this->stampService->getTodayAttendance($request->user());
        if (! $attendance || $attendance->status !== 'working') {
            return redirect()->route('attendance.index');
        }
        $this->stampService->clockOut($attendance);

        return redirect()->route('attendance.index');
    }

    public function breakStart(Request $request): RedirectResponse
    {
        $attendance = $this->stampService->getTodayAttendance($request->user());
        if (! $attendance || $attendance->status !== 'working') {
            return redirect()->route('attendance.index');
        }
        $this->stampService->breakStart($attendance);

        return redirect()->route('attendance.index');
    }

    public function breakEnd(Request $request): RedirectResponse
    {
        $attendance = $this->stampService->getTodayAttendance($request->user());
        if (! $attendance || $attendance->status !== 'on_break') {
            return redirect()->route('attendance.index');
        }
        $this->stampService->breakEnd($attendance);

        return redirect()->route('attendance.index');
    }
}
