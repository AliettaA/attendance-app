<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;

class DetailViewService
{
    /**
     * 勤怠詳細画面に表示する休憩入力行を作成する。
     *
     * @param  Attendance  $attendance  表示対象の勤怠
     * @param  CorrectionRequest|null  $pendingCorrectionRequest  未承認の修正申請
     * @return array<int, array<string, bool|int|string|null>>
     */
    public function createBreakRows(Attendance $attendance, ?CorrectionRequest $pendingCorrectionRequest): array
    {
        $breakRows = $attendance->breakTimes->values();
        $pendingBreakRows = $pendingCorrectionRequest?->correctionRequestBreaks?->values() ?? collect();
        $breakInputCount = max($breakRows->count(), $pendingBreakRows->count()) + 1;

        return collect(range(0, $breakInputCount - 1))
            ->map(function (int $index) use ($breakRows, $pendingBreakRows, $breakInputCount) {
                $breakTime = $breakRows->get($index);
                $pendingBreakTime = $pendingBreakRows->get($index);
                $displayBreakStartAt = $pendingBreakTime?->requested_break_start_at ?? $breakTime?->break_start_at;
                $displayBreakEndAt = $pendingBreakTime?->requested_break_end_at ?? $breakTime?->break_end_at;

                return [
                    'index' => $index,
                    'label' => '休憩'.($index + 1),
                    'original_break_time_id' => $breakTime?->id,
                    'start' => $this->formatTime($displayBreakStartAt),
                    'end' => $this->formatTime($displayBreakEndAt),
                    'is_last' => $index === $breakInputCount - 1,
                ];
            })
            ->all();
    }

    /**
     * 日時値を時刻表示用の H:i 形式に変換する。
     *
     * @param  mixed  $value  変換対象の日時値
     * @return string 時刻文字列。値がない場合は空文字
     */
    private function formatTime($value): string
    {
        return $value ? Carbon::parse($value)->format('H:i') : '';
    }
}
