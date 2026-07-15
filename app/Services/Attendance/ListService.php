<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ListService
{
    public function __construct(private SummaryService $summaryService) {}

    /**
     * 一般ユーザーの月次勤怠一覧に表示する日別行データを作成する。
     *
     * @param  User  $user  表示対象のユーザー
     * @param  Carbon  $month  表示対象月
     * @return array<int, array<string, string|null>>
     */
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
                ], $this->summaryService->attendanceSummary($attendance));
            })
            ->all();
    }
}
