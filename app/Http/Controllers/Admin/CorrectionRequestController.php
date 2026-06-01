<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorrectionRequestController extends Controller
{
    public function show($id)
    {
        $correctionRequest = CorrectionRequest::with([
            'user',
            'attendance.breakTimes',
            'correctionRequestBreaks.originalBreakTime',
        ])->findOrFail($id);

        return view('admin.correction_requests.approve', compact('correctionRequest'));
    }

    public function approve(Request $request, $id)
    {
        $correctionRequest = CorrectionRequest::with(['attendance.breakTimes', 'correctionRequestBreaks'])
            ->where('status', 'pending')
            ->findOrFail($id);

        DB::transaction(function () use ($correctionRequest, $request) {
            $attendance = $correctionRequest->attendance;

            $attendance->update([
                'clock_in_at' => $correctionRequest->requested_clock_in_at,
                'clock_out_at' => $correctionRequest->requested_clock_out_at,
                'note' => $correctionRequest->requested_note,
            ]);

            foreach ($correctionRequest->correctionRequestBreaks as $requestBreak) {
                if ($requestBreak->original_break_time_id) {
                    BreakTime::where('id', $requestBreak->original_break_time_id)
                        ->where('attendance_id', $attendance->id)
                        ->update([
                            'break_start_at' => $requestBreak->requested_break_start_at,
                            'break_end_at' => $requestBreak->requested_break_end_at,
                        ]);

                    continue;
                }

                if ($requestBreak->requested_break_start_at || $requestBreak->requested_break_end_at) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start_at' => $requestBreak->requested_break_start_at,
                        'break_end_at' => $requestBreak->requested_break_end_at,
                    ]);
                }
            }

            $correctionRequest->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => Carbon::now(),
            ]);
        });

        return redirect('/stamp_correction_request/list?status=approved')
            ->with('status', '修正申請を承認しました。');
    }
}
