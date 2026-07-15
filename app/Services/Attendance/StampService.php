<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;

class StampService
{
    /**
     * 指定ユーザーの当日勤怠を取得する。
     *
     * @param  User  $user  勤怠を取得するユーザー
     * @return Attendance|null 当日勤怠。未打刻の場合は null
     */
    public function getTodayAttendance(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->with('breakTimes')
            ->first();
    }

    /**
     * 指定ユーザーの出勤時刻を登録する。
     *
     * @param  User  $user  出勤するユーザー
     * @return Attendance 作成された勤怠
     */
    public function clockIn(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => Carbon::now(),
            'status' => 'working',
        ]);
    }

    /**
     * 指定勤怠の退勤時刻を登録する。
     *
     * @param  Attendance  $attendance  退勤対象の勤怠
     */
    public function clockOut(Attendance $attendance): void
    {
        $attendance->update([
            'clock_out_at' => Carbon::now(),
            'status' => 'finished',
        ]);
    }

    /**
     * 指定勤怠の休憩開始時刻を登録する。
     *
     * @param  Attendance  $attendance  休憩開始対象の勤怠
     * @return BreakTime 作成された休憩時間
     */
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

    /**
     * 進行中の休憩に休憩終了時刻を登録する。
     *
     * @param  Attendance  $attendance  休憩終了対象の勤怠
     */
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
