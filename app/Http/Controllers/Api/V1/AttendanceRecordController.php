<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        $query = Attendance::with(['user', 'breakTimes', 'correctionRequests'])
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('work_date', $request->input('date'));
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->input('month'));
            $query->whereBetween('work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        $attendanceRecords = $query->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    public function store(StoreAttendanceRecordRequest $request)
    {
        $attendanceRecord = Attendance::create([
            'user_id' => $request->input('user_id'),
            'work_date' => $request->input('date'),
            'clock_in_at' => Carbon::parse($request->input('date').' '.$request->input('clock_in')),
            'clock_out_at' => $request->filled('clock_out')
                ? Carbon::parse($request->input('date').' '.$request->input('clock_out'))
                : null,
            'status' => $request->filled('clock_out') ? 'finished' : 'working',
            'note' => $request->input('comment'),
        ]);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
        'user',
        'breakTimes',
        'correctionRequests',
    ]);

    return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord)
    {
        $this->authorize('update', $attendanceRecord);
        $attendanceRecord->update([
            'user_id' => $request->input('user_id'),
            'work_date' => $request->input('date'),
            'clock_in_at' => Carbon::parse($request->input('date').' '.$request->input('clock_in')),
            'clock_out_at' => $request->filled('clock_out')
                ? Carbon::parse($request->input('date').' '.$request->input('clock_out'))
                : null,
            'status' => $request->filled('clock_out') ? 'finished' : 'working',
            'note' => $request->input('comment'),
        ]);

        return new AttendanceRecordResource($attendanceRecord->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendanceRecord)
    {
        $this->authorize('delete', $attendanceRecord);
        $attendanceRecord->delete();

        return response()->noContent();
    }
}
