<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;

class SummaryService
{
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
