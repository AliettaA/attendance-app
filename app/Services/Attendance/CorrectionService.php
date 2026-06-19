<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;

class CorrectionService
{
    public function hasPendingCorrectionRequest(Attendance $attendance): bool
    {
        return $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    public function createCorrectionRequest(Attendance $attendance, User $user, array $validated, string $workDate): CorrectionRequest
    {
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => Carbon::parse($workDate.' '.$validated['clock_in_at']),
            'requested_clock_out_at' => Carbon::parse($workDate.' '.$validated['clock_out_at']),
            'requested_note' => $validated['note'],
            'status' => 'pending',
        ]);

        foreach ($validated['breaks'] ?? [] as $break) {
            $originalBreakTimeId = $break['original_break_time_id'] ?? null;

            if (empty($break['start']) && empty($break['end']) && ! $originalBreakTimeId) {
                continue;
            }

            CorrectionRequestBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'original_break_time_id' => $originalBreakTimeId,
                'requested_break_start_at' => ! empty($break['start'])
                    ? Carbon::parse($workDate.' '.$break['start'])
                    : null,
                'requested_break_end_at' => ! empty($break['end'])
                    ? Carbon::parse($workDate.' '.$break['end'])
                    : null,
            ]);
        }

        return $correctionRequest;
    }
}
