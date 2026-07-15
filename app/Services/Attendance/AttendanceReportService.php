<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    private const STANDARD_START_TIME = '09:00';

    private const STANDARD_END_TIME = '18:00';

    private const STANDARD_WORK_MINUTES = 480;

    private const LONG_WORK_MINUTES = 600;

    public function __construct(private SummaryService $summaryService) {}

    /**
     * ログインユーザーの過去6ヶ月分の勤怠レポートを作成する。
     *
     * @param  User  $user  レポート対象のユーザー
     * @return array{summary: array, monthly_reports: array, anomalies: array, standards: array}
     */
    public function build(User $user): array
    {
        $currentMonth = Carbon::today()->startOfMonth();
        $startMonth = $currentMonth->copy()->subMonths(5);
        $endMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->with('breakTimes')
            ->orderBy('work_date')
            ->get();

        $monthlyReports = $this->buildMonthlyReports($attendances, $startMonth, $currentMonth);
        $totalWorkMinutes = $monthlyReports->sum('total_work_minutes');
        $totalOvertimeMinutes = $monthlyReports->sum('total_overtime_minutes');
        $workDays = $monthlyReports->sum('work_days');

        return [
            'summary' => [
                'total_work_time' => $this->summaryService->formatMinutes($totalWorkMinutes, 'label'),
                'total_overtime_time' => $this->summaryService->formatMinutes($totalOvertimeMinutes, 'label'),
                'average_work_time' => $this->summaryService->formatMinutes($workDays > 0 ? intdiv($totalWorkMinutes, $workDays) : 0, 'label'),
                'work_days' => $workDays,
            ],
            'monthly_reports' => $monthlyReports->map(function (array $report) {
                return array_merge($report, [
                    'total_work_time' => $this->summaryService->formatMinutes($report['total_work_minutes'], 'label'),
                    'total_overtime_time' => $this->summaryService->formatMinutes($report['total_overtime_minutes'], 'label'),
                ]);
            })->all(),
            'anomalies' => $this->buildCurrentMonthAnomalies($attendances, $currentMonth),
            'standards' => [
                'start_time' => self::STANDARD_START_TIME,
                'end_time' => self::STANDARD_END_TIME,
                'long_work_time' => '10時間超',
            ],
        ];
    }

    /**
     * 対象期間の勤怠を月ごとの勤務日数・労働時間・残業時間に集計する。
     *
     * @param  Collection<int, Attendance>  $attendances  対象期間の勤怠コレクション
     * @param  Carbon  $startMonth  集計開始月
     * @param  Carbon  $currentMonth  集計終了月
     * @return Collection<int, array<string, int|string>>
     */
    private function buildMonthlyReports(Collection $attendances, Carbon $startMonth, Carbon $currentMonth): Collection
    {
        $period = CarbonPeriod::create($startMonth, '1 month', $currentMonth);

        return collect($period)->map(function (Carbon $month) use ($attendances) {
            $monthAttendances = $attendances
                ->filter(function (Attendance $attendance) use ($month) {
                    return Carbon::parse($attendance->work_date)->isSameMonth($month);
                });

            $totalWorkMinutes = $monthAttendances->sum(fn (Attendance $attendance) => $this->summaryService->workMinutes($attendance));
            $totalOvertimeMinutes = $monthAttendances->sum(function (Attendance $attendance) {
                return max($this->summaryService->workMinutes($attendance) - self::STANDARD_WORK_MINUTES, 0);
            });
            $workDays = $monthAttendances->filter(fn (Attendance $attendance) => $this->summaryService->workMinutes($attendance) > 0)->count();

            return [
                'month' => $month->format('Y年m月'),
                'total_work_minutes' => $totalWorkMinutes,
                'total_overtime_minutes' => $totalOvertimeMinutes,
                'average_work_minutes' => $workDays > 0 ? intdiv($totalWorkMinutes, $workDays) : 0,
                'work_days' => $workDays,
            ];
        });
    }

    /**
     * 当月の遅刻・早退・長時間労働の件数を集計する。
     *
     * @param  Collection<int, Attendance>  $attendances  対象期間の勤怠コレクション
     * @param  Carbon  $currentMonth  異常検知の対象月
     * @return array{late_count: int, early_leave_count: int, long_work_count: int}
     */
    private function buildCurrentMonthAnomalies(Collection $attendances, Carbon $currentMonth): array
    {
        $currentMonthAttendances = $attendances->filter(function (Attendance $attendance) use ($currentMonth) {
            return Carbon::parse($attendance->work_date)->isSameMonth($currentMonth);
        });

        return [
            'late_count' => $currentMonthAttendances->filter(function (Attendance $attendance) {
                return $attendance->clock_in_at
                    && Carbon::parse($attendance->clock_in_at)->format('H:i') > self::STANDARD_START_TIME;
            })->count(),
            'early_leave_count' => $currentMonthAttendances->filter(function (Attendance $attendance) {
                return $attendance->clock_out_at
                    && Carbon::parse($attendance->clock_out_at)->format('H:i') < self::STANDARD_END_TIME;
            })->count(),
            'long_work_count' => $currentMonthAttendances->filter(function (Attendance $attendance) {
                return $this->summaryService->workMinutes($attendance) > self::LONG_WORK_MINUTES;
            })->count(),
        ];
    }
}
