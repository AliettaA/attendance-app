<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;

class SummaryService
{
    /**
     * 勤怠に紐づく休憩時間の合計分数を計算する。
     *
     * @param  Attendance|null  $attendance  集計対象の勤怠
     * @return int 休憩時間の合計分数
     */
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

    /**
     * 出勤から退勤までの時間から休憩時間を引いた実働分数を計算する。
     *
     * @param  Attendance|null  $attendance  集計対象の勤怠
     * @return int 実働時間の分数
     */
    public function workMinutes(?Attendance $attendance): int
    {
        if (! $attendance || ! $attendance->clock_in_at || ! $attendance->clock_out_at) {
            return 0;
        }

        $workMinutes = Carbon::parse($attendance->clock_in_at)
            ->diffInMinutes(Carbon::parse($attendance->clock_out_at)) - $this->breakMinutes($attendance);

        return max($workMinutes, 0);
    }

    /**
     * 分数を画面表示用の時間表記に変換する。
     *
     * @param  int  $minutes  変換対象の分数
     * @param  string  $format  colon は H:MM、label は HhMMm 形式で返す
     * @return string 整形済みの時間文字列
     */
    public function formatMinutes(int $minutes, string $format = 'colon'): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($format === 'label') {
            return sprintf('%dh%02dm', $hours, $remainingMinutes);
        }

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }

    /**
     * 一覧表示で使用する出勤・退勤・休憩・実働時間の値を作成する。
     *
     * @param  Attendance|null  $attendance  表示対象の勤怠
     * @return array{clock_in: string, clock_out: string, break_time: string, work_time: string}
     */
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
