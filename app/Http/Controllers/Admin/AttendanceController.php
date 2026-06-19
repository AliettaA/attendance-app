<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Services\Attendance\DetailViewService;
use App\Services\Attendance\SummaryService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(
        private DetailViewService $detailViewService,
        private SummaryService $summaryService
    ) {}

    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $users = User::where('role', 'user')
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('work_date', $date->toDateString())
                    ->with('breakTimes');
            }])
            ->orderBy('name')
            ->get();

        $attendanceRows = $users->map(function (User $user) {
            $attendance = $user->attendances->first();

            return array_merge([
                'name' => $user->name,
                'detail_url' => $attendance ? route('admin.attendance.show', ['id' => $attendance->id]) : null,
            ], $this->summaryService->attendanceSummary($attendance));
        });

        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('admin.attendance.list', compact('date', 'attendanceRows', 'previousDate', 'nextDate'));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes', 'correctionRequests'])
            ->findOrFail($id);
        $pendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->first();
        $breakInputRows = $this->detailViewService->createBreakRows($attendance, $pendingCorrectionRequest);

        return view('admin.attendance.detail', compact('attendance', 'pendingCorrectionRequest', 'breakInputRows'));
    }

    public function createForStaffDate(Request $request, $id)
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $workDate = Carbon::parse($request->query('date', today()->toDateString()))->toDateString();
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

    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with(['breakTimes', 'correctionRequests'])->findOrFail($id);
        $hasPendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->isNotEmpty();

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

    public function storeForStaffDate(AttendanceCorrectionRequest $request, $id)
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

    public function staff(Request $request, $id)
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('breakTimes')
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn (Attendance $attendance) => Carbon::parse($attendance->work_date)->toDateString());

        $dates = collect(CarbonPeriod::create($startOfMonth, $endOfMonth));
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $attendanceRows = $dates->map(function (Carbon $date) use ($attendances, $user, $weekdays) {
            $attendance = $attendances->get($date->toDateString());

            return array_merge([
                'date' => $date->format('m/d').'（'.$weekdays[$date->dayOfWeek].'）',
                'detail_url' => $attendance
                    ? route('admin.attendance.show', ['id' => $attendance->id])
                    : route('admin.attendance.staff.detail.create', ['id' => $user->id, 'date' => $date->toDateString()]),
            ], $this->summaryService->attendanceSummary($attendance));
        });

        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.staff', compact('user', 'month', 'attendanceRows', 'previousMonth', 'nextMonth'));
    }

    public function exportStaffCsv(Request $request, $id)
    {
        $user = User::where('role', 'user')->findOrFail($id);
        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->with('breakTimes')
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn (Attendance $attendance) => Carbon::parse($attendance->work_date)->toDateString());

        $dates = collect(CarbonPeriod::create(
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth()
        ));

        $fileName = 'attendance_'.$user->id.'_'.$month->format('Y_m').'.csv';

        return response()->streamDownload(function () use ($dates, $attendances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($dates as $date) {
                $attendance = $attendances->get($date->toDateString());

                if (! $attendance) {
                    fputcsv($handle, [$date->format('Y/m/d'), '', '', '', '']);

                    continue;
                }

                $summary = $this->summaryService->attendanceSummary($attendance);

                fputcsv($handle, [
                    Carbon::parse($attendance->work_date)->format('Y/m/d'),
                    $summary['clock_in'],
                    $summary['clock_out'],
                    $summary['break_time'],
                    $summary['work_time'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
