<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Services\Attendance\CorrectionService;
use App\Services\Attendance\DetailViewService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DetailController extends Controller
{
    public function __construct(
        private CorrectionService $correctionService,
        private DetailViewService $detailViewService
    ) {}

    public function createByDate(Request $request)
    {
        $workDate = Carbon::parse($request->query('date', today()->toDateString()))->toDateString();
        $existingAttendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('work_date', $workDate)
            ->first();

        if ($existingAttendance) {
            return redirect()->route('attendance.detail.show', ['id' => $existingAttendance->id]);
        }

        $attendance = new Attendance([
            'user_id' => $request->user()->id,
            'work_date' => $workDate,
            'status' => 'finished',
        ]);
        $attendance->setRelation('user', $request->user());
        $attendance->setRelation('breakTimes', collect());
        $attendance->setRelation('correctionRequests', collect());
        $pendingCorrectionRequest = null;
        $breakInputRows = $this->detailViewService->createBreakRows($attendance, $pendingCorrectionRequest);

        return view('attendance.detail', compact('attendance', 'pendingCorrectionRequest', 'breakInputRows'));
    }

    public function show(Request $request, $id)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->with(['user', 'breakTimes', 'correctionRequests.correctionRequestBreaks'])
            ->findOrFail($id);

        $pendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();
        $breakInputRows = $this->detailViewService->createBreakRows($attendance, $pendingCorrectionRequest);

        return view('attendance.detail', compact('attendance', 'pendingCorrectionRequest', 'breakInputRows'));
    }

    public function storeCorrectionRequest(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->with(['breakTimes', 'correctionRequests'])
            ->findOrFail($id);
        if ($this->correctionService->hasPendingCorrectionRequest($attendance)) {
            return redirect()->route('attendance.detail.show', ['id' => $attendance->id]);
        }

        $workDate = Carbon::parse($attendance->work_date)->toDateString();
        $this->correctionService->createCorrectionRequest(
            $attendance,
            $request->user(),
            $request->validated(),
            $workDate
        );

        return redirect()->route('attendance.detail.show', ['id' => $attendance->id])
            ->with('status', '修正申請を送信しました。');
    }

    public function storeCorrectionRequestByDate(AttendanceCorrectionRequest $request)
    {
        $workDate = Carbon::parse($request->input('work_date'))->toDateString();
        $validated = $request->validated();

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'work_date' => $workDate,
            ],
            [
                'status' => 'finished',
            ]
        );

        if ($this->correctionService->hasPendingCorrectionRequest($attendance)) {
            return redirect()->route('attendance.detail.show', ['id' => $attendance->id]);
        }

        $this->correctionService->createCorrectionRequest(
            $attendance,
            $request->user(),
            $validated,
            $workDate
        );

        return redirect()->route('attendance.detail.show', ['id' => $attendance->id])
            ->with('status', '修正申請を送信しました。');
    }
}
