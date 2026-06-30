<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'date' => Carbon::parse($this->work_date)->format('Y-m-d'),
            'clock_in' => $this->clock_in_at ? Carbon::parse($this->clock_in_at)->format('H:i:s') : null,
            'clock_out' => $this->clock_out_at ? Carbon::parse($this->clock_out_at)->format('H:i:s') : null,
            'total_time' => $this->formatMinutes($this->workMinutes()),
            'total_break_time' => $this->formatMinutes($this->breakMinutes()),
            'status' => $this->status,
            'comment' => $this->note,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            'breaks' => $this->whenLoaded('breakTimes', function () {
                return $this->breakTimes->map(function ($breakTime) {
                    return [
                        'id' => $breakTime->id,
                        'break_in' => $breakTime->break_start_at
                            ? Carbon::parse($breakTime->break_start_at)->format('H:i:s')
                            : null,
                        'break_out' => $breakTime->break_end_at
                            ? Carbon::parse($breakTime->break_end_at)->format('H:i:s')
                            : null,
                    ];
                });
            }),

            'applications' => $this->whenLoaded('correctionRequests', function () {
                return $this->correctionRequests->map(function ($correctionRequest) {
                    return [
                        'id' => $correctionRequest->id,
                        'status' => $correctionRequest->status,
                        'requested_clock_in' => $correctionRequest->requested_clock_in_at
                            ? Carbon::parse($correctionRequest->requested_clock_in_at)->format('H:i:s')
                            : null,
                        'requested_clock_out' => $correctionRequest->requested_clock_out_at
                            ? Carbon::parse($correctionRequest->requested_clock_out_at)->format('H:i:s')
                            : null,
                        'requested_comment' => $correctionRequest->requested_note,
                    ];
                });
            }),
        ];
    }

    private function breakMinutes(): int
    {
        if (! $this->relationLoaded('breakTimes')) {
            return 0;
        }

        return $this->breakTimes->sum(function ($breakTime) {
            if (! $breakTime->break_start_at || ! $breakTime->break_end_at) {
                return 0;
            }

            return Carbon::parse($breakTime->break_start_at)
                ->diffInMinutes(Carbon::parse($breakTime->break_end_at));
        });
    }

    private function workMinutes(): int
    {
        if (! $this->clock_in_at || ! $this->clock_out_at) {
            return 0;
        }

        $workMinutes = Carbon::parse($this->clock_in_at)
            ->diffInMinutes(Carbon::parse($this->clock_out_at)) - $this->breakMinutes();

        return max($workMinutes, 0);
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
