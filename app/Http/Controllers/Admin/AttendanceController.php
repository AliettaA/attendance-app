<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Services\Attendance\AdminAttendanceService;
use App\Services\Attendance\DetailViewService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private DetailViewService $detailViewService,
        private AdminAttendanceService $adminAttendanceService
    ) {}

    public function index(Request $request): View
    {
        $date = $this->parseDateOrToday($request->query('date'));
        $attendanceRows = $this->adminAttendanceService->createDailyRows($date);
        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('admin.attendance.list', compact('date', 'attendanceRows', 'previousDate', 'nextDate'));
    }

    public function show($id): View
    {
        $attendance = Attendance::with(['user', 'breakTimes', 'correctionRequests.correctionRequestBreaks'])
            ->findOrFail($id);
        $pendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->first();
        $breakInputRows = $this->detailViewService->createBreakRows($attendance, $pendingCorrectionRequest);

        return view('admin.attendance.detail', compact('attendance', 'pendingCorrectionRequest', 'breakInputRows'));
    }

    public function createForStaffDate(Request $request, $id): View|RedirectResponse
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $workDate = $this->parseDateOrToday($request->query('date'))->toDateString();
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $workDate)
            ->first();

        if ($existingAttendance) {
            return redirect()->route('admin.attendance.show', ['id' => $existingAttendance->id]);
        }

        $attendance = new Attendance([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'status' => 'finished',
        ]);
        $attendance->setRelation('user', $user);
        $attendance->setRelation('breakTimes', collect());
        $attendance->setRelation('correctionRequests', collect());
        $pendingCorrectionRequest = null;
        $breakInputRows = $this->detailViewService->createBreakRows($attendance, $pendingCorrectionRequest);

        return view('admin.attendance.detail', compact('attendance', 'pendingCorrectionRequest', 'breakInputRows'));
    }

    public function update(AttendanceCorrectionRequest $request, $id): RedirectResponse
    {
        $attendance = Attendance::findOrFail($id);
        $hasPendingCorrectionRequest = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingCorrectionRequest) {
            return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
        }

        $workDate = Carbon::parse($attendance->work_date)->toDateString();
        $validated = $request->validated();

        DB::transaction(function () use ($attendance, $validated, $workDate) {
            $attendance->update([
                'clock_in_at' => Carbon::parse($workDate.' '.$validated['clock_in_at']),
                'clock_out_at' => Carbon::parse($workDate.' '.$validated['clock_out_at']),
                'note' => $validated['note'],
            ]);

            foreach ($validated['breaks'] ?? [] as $break) {
                $originalBreakTimeId = $break['original_break_time_id'] ?? null;
                $breakStart = $break['start'] ?? null;
                $breakEnd = $break['end'] ?? null;

                if (! $breakStart && ! $breakEnd) {
                    if ($originalBreakTimeId) {
                        BreakTime::where('id', $originalBreakTimeId)
                            ->where('attendance_id', $attendance->id)
                            ->delete();
                    }

                    continue;
                }

                $values = [
                    'break_start_at' => Carbon::parse($workDate.' '.$breakStart),
                    'break_end_at' => Carbon::parse($workDate.' '.$breakEnd),
                ];

                if ($originalBreakTimeId) {
                    BreakTime::where('id', $originalBreakTimeId)
                        ->where('attendance_id', $attendance->id)
                        ->update($values);

                    continue;
                }

                BreakTime::create(array_merge($values, [
                    'attendance_id' => $attendance->id,
                ]));
            }
        });

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id])
            ->with('status', '勤怠情報を更新しました。');
    }

    public function storeForStaffDate(AttendanceCorrectionRequest $request, $id): RedirectResponse
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $workDate = Carbon::parse($request->input('work_date'))->toDateString();
        $validated = $request->validated();

        $attendance = DB::transaction(function () use ($user, $validated, $workDate) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $workDate,
                'clock_in_at' => Carbon::parse($workDate.' '.$validated['clock_in_at']),
                'clock_out_at' => Carbon::parse($workDate.' '.$validated['clock_out_at']),
                'note' => $validated['note'],
                'status' => 'finished',
            ]);

            foreach ($validated['breaks'] ?? [] as $break) {
                if (empty($break['start']) && empty($break['end'])) {
                    continue;
                }

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => Carbon::parse($workDate.' '.$break['start']),
                    'break_end_at' => Carbon::parse($workDate.' '.$break['end']),
                ]);
            }

            return $attendance;
        });

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id])
            ->with('status', '勤怠情報を更新しました。');
    }

    public function staff(Request $request, $id): View
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $month = $this->parseDateOrToday($request->query('month'));
        $attendanceRows = $this->adminAttendanceService->createStaffMonthlyRows($user, $month);
        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.staff', compact('user', 'month', 'attendanceRows', 'previousMonth', 'nextMonth'));
    }

    public function exportStaffCsv(Request $request, $id): StreamedResponse
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $month = $this->parseDateOrToday($request->query('month'));

        return $this->adminAttendanceService->streamStaffCsv($user, $month);
    }

    private function parseDateOrToday(?string $value): Carbon
    {
        if (! $value) {
            return Carbon::today();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return Carbon::today();
        }
    }
}
