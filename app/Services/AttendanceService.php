<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceService
{
    public function getTodayAttendance(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->with('breakTimes')
            ->first();
    }

    public function clockIn(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => Carbon::now(),
            'status' => 'working',
        ]);
    }

    public function clockOut(Attendance $attendance): void
    {
        $attendance->update([
            'clock_out_at' => Carbon::now(),
            'status' => 'finished',
        ]);
    }

    public function breakStart(Attendance $attendance): BreakTime
    {
        $attendance->update([
            'status' => 'on_break',
        ]);

        return BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now(),
        ]);
    }

    public function breakEnd(Attendance $attendance): void
    {
        $breakTime = $attendance->breakTimes()
            ->whereNull('break_end_at')
            ->latest()
            ->first();
        if ($breakTime) {
            $breakTime->update([
                'break_end_at' => Carbon::now(),
            ]);
        }
        $attendance->update([
            'status' => 'working',
        ]);
    }

    public function hasPendingCorrectionRequest(Attendance $attendance): bool
    {
        return $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    public function createCorrectionRequest(Attendance $attendance, User $user, array $validated, string $workDate): CorrectionRequest
    {
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => Carbon::parse($workDate.' '.$validated['clock_in_at']),
            'requested_clock_out_at' => Carbon::parse($workDate.' '.$validated['clock_out_at']),
            'requested_note' => $validated['note'],
            'status' => 'pending',
        ]);

        foreach ($validated['breaks'] ?? [] as $break) {
            $originalBreakTimeId = $break['original_break_time_id'] ?? null;

            if (empty($break['start']) && empty($break['end']) && ! $originalBreakTimeId) {
                continue;
            }

            CorrectionRequestBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'original_break_time_id' => $originalBreakTimeId,
                'requested_break_start_at' => ! empty($break['start'])
                    ? Carbon::parse($workDate.' '.$break['start'])
                    : null,
                'requested_break_end_at' => ! empty($break['end'])
                    ? Carbon::parse($workDate.' '.$break['end'])
                    : null,
            ]);
        }

        return $correctionRequest;
    }

    public function createMonthlyRows(User $user, Carbon $month): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('breakTimes')
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn (Attendance $attendance) => Carbon::parse($attendance->work_date)->toDateString());

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return collect(CarbonPeriod::create($startOfMonth, $endOfMonth))
            ->map(function (Carbon $date) use ($attendances, $weekdays) {
                $attendance = $attendances->get($date->toDateString());

                return array_merge([
                    'date' => $date->format('m/d').'（'.$weekdays[$date->dayOfWeek].'）',
                    'detail_url' => $attendance
                        ? route('attendance.detail.show', ['id' => $attendance->id])
                        : route('attendance.detail.create', ['date' => $date->toDateString()]),
                ], $this->attendanceSummary($attendance));
            })
            ->all();
    }

    public function breakMinutes(?Attendance $attendance): int
    {
        if (! $attendance) {
            return 0;
        }

        return $attendance->breakTimes->sum(function ($breakTime) {
            if (! $breakTime->break_start_at || ! $breakTime->break_end_at) {
                return 0;
            }

            return Carbon::parse($breakTime->break_start_at)
                ->diffInMinutes(Carbon::parse($breakTime->break_end_at));
        });
    }

    public function workMinutes(?Attendance $attendance): int
    {
        if (! $attendance || ! $attendance->clock_in_at || ! $attendance->clock_out_at) {
            return 0;
        }

        $workMinutes = Carbon::parse($attendance->clock_in_at)
            ->diffInMinutes(Carbon::parse($attendance->clock_out_at)) - $this->breakMinutes($attendance);

        return max($workMinutes, 0);
    }

    public function formatMinutes(int $minutes, string $format = 'colon'): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($format === 'label') {
            return sprintf('%dh%02dm', $hours, $remainingMinutes);
        }

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }

    public function attendanceSummary(?Attendance $attendance): array
    {
        if (! $attendance) {
            return [
                'clock_in' => '',
                'clock_out' => '',
                'break_time' => '',
                'work_time' => '',
            ];
        }

        return [
            'clock_in' => $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '',
            'clock_out' => $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '',
            'break_time' => $this->formatMinutes($this->breakMinutes($attendance)),
            'work_time' => $this->formatMinutes($this->workMinutes($attendance)),
        ];
    }
}
