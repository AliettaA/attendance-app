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
            'date' => $this->work_date
                ? Carbon::parse($this->work_date)->format('Y-m-d')
                : null,
            'clock_in' => $this->clock_in_at
                ? Carbon::parse($this->clock_in_at)->format('H:i:s')
                : null,
            'clock_out' => $this->clock_out_at
                ? Carbon::parse($this->clock_out_at)->format('H:i:s')
                : null,
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
                        'break_start' => $breakTime->break_start_at
                            ? Carbon::parse($breakTime->break_start_at)->format('H:i:s')
                            : null,
                        'break_end' => $breakTime->break_end_at
                            ? Carbon::parse($breakTime->break_end_at)->format('H:i:s')
                            : null,
                    ];
                });
            }),
            'correction_requests' => $this->whenLoaded('correctionRequests', function () {
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
}