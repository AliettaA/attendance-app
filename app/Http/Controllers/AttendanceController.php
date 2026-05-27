<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService)
    {
    }

    public function index(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        return view('attendance.index', compact('attendance'));
    }

    public function clockIn(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if ($attendance) {
            return redirect('/attendance');
        }
        $this->attendanceService->clockIn($request->user());
        return redirect('/attendance');
    }

    public function clockOut(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if (!$attendance || $attendance->status !== 'working') {
            return redirect('/attendance');
        }
        $this->attendanceService->clockOut($attendance);
        return redirect('/attendance');
    }

    public function breakStart(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if (!$attendance || $attendance->status !== 'working') {
            return redirect('/attendance');
        }
        $this->attendanceService->breakStart($attendance);
        return redirect('/attendance');
    }
    public function breakEnd(Request $request)
    {
        $attendance = $this->attendanceService->getTodayAttendance($request->user());
        if (!$attendance || $attendance->status !== 'on_break') {
            return redirect('/attendance');
        }
        $this->attendanceService->breakEnd($attendance);
        return redirect('/attendance');
    }

    public function list(Request $request)
    {
        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $attendances = Attendance::where('user_id', $request->user()->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('breakTimes')
            ->orderBy('work_date')
            ->get();
        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        return view('attendance.list', compact(
            'month',
            'attendances',
            'previousMonth',
            'nextMonth'
        ));
    }
    public function show(Request $request, $id)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->with(['breakTimes', 'correctionRequests'])
            ->findOrFail($id);
        $pendingCorrectionRequest = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();
        return view('attendance.detail', compact('attendance', 'pendingCorrectionRequest'));
    }
    public function storeCorrectionRequest(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->with(['breakTimes', 'correctionRequests'])
            ->findOrFail($id);
        $hasPendingRequest = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();
        if ($hasPendingRequest) {
            return redirect("/attendance/detail/{$attendance->id}");
        }
        $workDate = Carbon::parse($attendance->work_date)->toDateString();
        $validated = $request->validated();
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $request->user()->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => Carbon::parse($workDate . ' ' . $validated['clock_in_at']),
            'requested_clock_out_at' => Carbon::parse($workDate . ' ' . $validated['clock_out_at']),
            'requested_note' => $validated['note'],
            'status' => 'pending',
        ]);
        foreach ($validated['breaks'] ?? [] as $break) {
            if (empty($break['start']) && empty($break['end'])) {
                continue;
            }
            CorrectionRequestBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'original_break_time_id' => $break['original_break_time_id'] ?? null,
                'requested_break_start_at' => ! empty($break['start'])
                    ? Carbon::parse($workDate . ' ' . $break['start'])
                    : null,
                'requested_break_end_at' => ! empty($break['end'])
                    ? Carbon::parse($workDate . ' ' . $break['end'])
                    : null,
            ]);
        }
        return redirect("/attendance/detail/{$attendance->id}");
    }
}