<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;

class StampService
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
            ->latest('break_start_at')
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
}
