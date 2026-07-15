<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を検索条件とページネーション付きでJSON返却する。
     *
     * @param  IndexAttendanceRecordRequest  $request  一覧検索用のリクエスト
     * @return AnonymousResourceCollection 勤怠一覧リソースのコレクション
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $query = Attendance::with(['user', 'breakTimes'])
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('user_id'));
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('work_date', $request->input('date'));
            })
            ->when($request->filled('month'), function ($query) use ($request) {
                $month = Carbon::parse($request->input('month'));

                $query->whereBetween('work_date', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ]);
            })
            ->latest('work_date');

        $perPage = min((int) $request->input('per_page', 20), 100);

        $attendanceRecords = $query->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * API経由で新しい勤怠レコードを作成する。
     *
     * @param  StoreAttendanceRecordRequest  $request  勤怠登録用のリクエスト
     * @return JsonResponse 作成した勤怠レコードのJSONレスポンス
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = Attendance::create([
            'user_id' => $request->user()->id,
            'work_date' => $request->input('date'),
            'clock_in_at' => Carbon::parse($request->input('date').' '.$request->input('clock_in')),
            'clock_out_at' => $request->filled('clock_out')
                ? Carbon::parse($request->input('date').' '.$request->input('clock_out'))
                : null,
            'status' => $request->filled('clock_out') ? 'finished' : 'working',
            'note' => $request->input('comment'),
        ]);

        return (new AttendanceRecordResource(
            $attendanceRecord->load(['user', 'breakTimes', 'correctionRequests'])
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 指定された勤怠レコードの詳細をJSON返却する。
     *
     * @param  Attendance  $attendanceRecord  詳細取得対象の勤怠
     * @return AttendanceRecordResource 勤怠詳細リソース
     */
    public function show(Attendance $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load([
            'user',
            'breakTimes',
            'correctionRequests',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * API経由で指定された勤怠レコードを更新する。
     *
     * @param  UpdateAttendanceRecordRequest  $request  勤怠更新用のリクエスト
     * @param  Attendance  $attendanceRecord  更新対象の勤怠
     * @return AttendanceRecordResource 更新後の勤怠詳細リソース
     */
    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);
        $attendanceRecord->update([
            'work_date' => $request->input('date'),
            'clock_in_at' => Carbon::parse($request->input('date').' '.$request->input('clock_in')),
            'clock_out_at' => $request->filled('clock_out')
                ? Carbon::parse($request->input('date').' '.$request->input('clock_out'))
                : null,
            'status' => $request->filled('clock_out') ? 'finished' : 'working',
            'note' => $request->input('comment'),
        ]);

        return new AttendanceRecordResource(
            $attendanceRecord->fresh()->load(['user', 'breakTimes', 'correctionRequests'])
        );
    }

    /**
     * API経由で指定された勤怠レコードを削除する。
     *
     * @param  Attendance  $attendanceRecord  削除対象の勤怠
     * @return Response 空レスポンス
     */
    public function destroy(Attendance $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);
        $attendanceRecord->delete();

        return response()->noContent();
    }
}
