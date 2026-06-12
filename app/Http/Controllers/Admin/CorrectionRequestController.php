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

        $workDate = Carbon::parse($correctionRequest->attendance->work_date);
        $breakRows = $correctionRequest->correctionRequestBreaks
            ->values()
            ->map(function ($requestBreak) {
                return [
                    'start' => $this->formatTime($requestBreak->requested_break_start_at),
                    'end' => $this->formatTime($requestBreak->requested_break_end_at),
                ];
            });

        while ($breakRows->count() < 2) {
            $breakRows->push([
                'start' => '',
                'end' => '',
            ]);
        }

        $correctionRequestView = [
            'id' => $correctionRequest->id,
            'status' => $correctionRequest->status,
            'user_name' => $correctionRequest->user->name,
            'work_year' => $workDate->format('Y年'),
            'work_date' => $workDate->format('n月 j日'),
            'clock_in' => $this->formatTime($correctionRequest->requested_clock_in_at),
            'clock_out' => $this->formatTime($correctionRequest->requested_clock_out_at),
            'break_rows' => $breakRows,
            'note' => $correctionRequest->requested_note,
        ];

        return view('admin.correction_requests.approve', compact('correctionRequestView'));
    }

    private function formatTime($time): string
    {
        return $time ? Carbon::parse($time)->format('H:i') : '';
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
                    if (! $requestBreak->requested_break_start_at && ! $requestBreak->requested_break_end_at) {
                        BreakTime::where('id', $requestBreak->original_break_time_id)
                            ->where('attendance_id', $attendance->id)
                            ->delete();

                        continue;
                    }

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

        return redirect()->route('correction_requests.index', ['status' => 'pending'])
            ->with('status', '修正申請を承認しました。');
    }
}
