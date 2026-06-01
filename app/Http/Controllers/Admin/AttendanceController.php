<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $date->toDateString())
            ->get()
            ->sortBy('user.name');

        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('admin.attendance.list', compact('date', 'attendances', 'previousDate', 'nextDate'));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes', 'correctionRequests'])
            ->findOrFail($id);
        $pendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->first();

        return view('admin.attendance.detail', compact('attendance', 'pendingCorrectionRequest'));
    }

    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with(['breakTimes', 'correctionRequests'])->findOrFail($id);
        $hasPendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->isNotEmpty();

        if ($hasPendingCorrectionRequest) {
            return redirect("/admin/attendance/{$attendance->id}");
        }

        $workDate = Carbon::parse($attendance->work_date)->toDateString();
        $validated = $request->validated();

        DB::transaction(function () use ($attendance, $validated, $workDate) {
            $attendance->update([
                'clock_in_at' => Carbon::parse($workDate . ' ' . $validated['clock_in_at']),
                'clock_out_at' => Carbon::parse($workDate . ' ' . $validated['clock_out_at']),
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
                    'break_start_at' => Carbon::parse($workDate . ' ' . $breakStart),
                    'break_end_at' => Carbon::parse($workDate . ' ' . $breakEnd),
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

        return redirect("/admin/attendance/{$attendance->id}")
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

        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.staff', compact('user', 'month', 'dates', 'attendances', 'previousMonth', 'nextMonth'));
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

        $fileName = 'attendance_' . $user->id . '_' . $month->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($dates, $attendances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($dates as $date) {
                $attendance = $attendances->get($date->toDateString());

                if (! $attendance) {
                    fputcsv($handle, [$date->format('Y/m/d'), '', '', '', '']);
                    continue;
                }

                $breakMinutes = $attendance->breakTimes->sum(function ($breakTime) {
                    if (! $breakTime->break_start_at || ! $breakTime->break_end_at) {
                        return 0;
                    }

                    return Carbon::parse($breakTime->break_start_at)
                        ->diffInMinutes(Carbon::parse($breakTime->break_end_at));
                });
                $workMinutes = 0;

                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                    $workMinutes = Carbon::parse($attendance->clock_in_at)
                        ->diffInMinutes(Carbon::parse($attendance->clock_out_at)) - $breakMinutes;
                }

                fputcsv($handle, [
                    Carbon::parse($attendance->work_date)->format('Y/m/d'),
                    $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '',
                    $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '',
                    sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60),
                    sprintf('%d:%02d', intdiv(max($workMinutes, 0), 60), max($workMinutes, 0) % 60),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
