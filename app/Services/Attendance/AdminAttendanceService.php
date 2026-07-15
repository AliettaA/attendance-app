<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceService
{
    public function __construct(private SummaryService $summaryService) {}

    /**
     * 管理者の日次勤怠一覧に表示する全一般ユーザーの行データを作成する。
     *
     * @param  Carbon  $date  表示対象日
     * @return Collection<int, array<string, string|null>>
     */
    public function createDailyRows(Carbon $date): Collection
    {
        return User::where('role', 'user')
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('work_date', $date->toDateString())
                    ->with('breakTimes');
            }])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $attendance = $user->attendances->first();

                return array_merge([
                    'name' => $user->name,
                    'detail_url' => $attendance ? route('admin.attendance.show', ['id' => $attendance->id]) : null,
                ], $this->summaryService->attendanceSummary($attendance));
            });
    }

    /**
     * 管理者のスタッフ別月次勤怠一覧に表示する日別行データを作成する。
     *
     * @param  User  $user  表示対象のスタッフ
     * @param  Carbon  $month  表示対象月
     * @return Collection<int, array<string, string|null>>
     */
    public function createStaffMonthlyRows(User $user, Carbon $month): Collection
    {
        $attendances = $this->staffAttendances($user, $month);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return $this->monthDates($month)
            ->map(function (Carbon $date) use ($attendances, $user, $weekdays) {
                $attendance = $attendances->get($date->toDateString());

                return array_merge([
                    'date' => $date->format('m/d').'（'.$weekdays[$date->dayOfWeek].'）',
                    'detail_url' => $attendance
                        ? route('admin.attendance.show', ['id' => $attendance->id])
                        : route('admin.attendance.staff.detail.create', [
                            'id' => $user->id,
                            'date' => $date->toDateString(),
                        ]),
                ], $this->summaryService->attendanceSummary($attendance));
            });
    }

    /**
     * 指定スタッフの月次勤怠をCSVとしてダウンロードするレスポンスを作成する。
     *
     * @param  User  $user  CSV出力対象のスタッフ
     * @param  Carbon  $month  CSV出力対象月
     * @return StreamedResponse CSVダウンロードレスポンス
     */
    public function streamStaffCsv(User $user, Carbon $month): StreamedResponse
    {
        $dates = $this->monthDates($month);
        $attendances = $this->staffAttendances($user, $month);
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

    /**
     * 指定スタッフの対象月の勤怠を日付キーで取得する。
     *
     * @param  User  $user  取得対象のスタッフ
     * @param  Carbon  $month  取得対象月
     * @return Collection<string, Attendance>
     */
    private function staffAttendances(User $user, Carbon $month): Collection
    {
        return Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->with('breakTimes')
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn (Attendance $attendance) => Carbon::parse($attendance->work_date)->toDateString());
    }

    /**
     * 指定月の月初から月末までの日付コレクションを作成する。
     *
     * @param  Carbon  $month  対象月
     * @return Collection<int, Carbon>
     */
    private function monthDates(Carbon $month): Collection
    {
        return collect(CarbonPeriod::create(
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth()
        ));
    }
}
