<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CorrectionService
{
    /**
     * 指定した勤怠に未承認の修正申請が存在するか判定する。
     *
     * @param  Attendance  $attendance  判定対象の勤怠
     * @return bool 未承認の修正申請がある場合は true
     */
    public function hasPendingCorrectionRequest(Attendance $attendance): bool
    {
        return $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * 勤怠修正申請と申請用の休憩時間をトランザクション内で作成する。
     *
     * @param  Attendance  $attendance  修正対象の勤怠
     * @param  User  $user  申請者
     * @param  array  $validated  バリデーション済みの申請値
     * @param  string  $workDate  勤務日
     * @return CorrectionRequest 作成された修正申請
     */
    public function createCorrectionRequest(Attendance $attendance, User $user, array $validated, string $workDate): CorrectionRequest
    {
        return DB::transaction(function () use ($attendance, $user, $validated, $workDate) {
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
        });
    }
}
