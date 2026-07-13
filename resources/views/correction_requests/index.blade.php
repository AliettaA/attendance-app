@extends('layouts.app')

@section('title', '申請一覧')

@section('content')
    <div class="page-container correction-request-list-page">
        <h1 class="page-title mb-8">申請一覧</h1>

        <div class="correction-request-tabs">
            <a href="{{ route('correction_requests.index', ['status' => 'pending']) }}"
                class="correction-request-tab {{ $status === 'pending' ? 'font-semibold text-black' : 'text-gray-700' }}">
                承認待ち
            </a>
            <a href="{{ route('correction_requests.index', ['status' => 'approved']) }}"
                class="correction-request-tab {{ $status === 'approved' ? 'font-semibold text-black' : 'text-gray-700' }}">
                承認済み
            </a>
        </div>

        <div class="table-panel">
            <table class="data-table">
                <thead class="text-sm">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left">状態</th>
                        <th scope="col" class="px-4 py-3 text-left">名前</th>
                        <th scope="col" class="px-4 py-3 text-left">対象日時</th>
                        <th scope="col" class="px-4 py-3 text-left">申請理由</th>
                        <th scope="col" class="px-4 py-3 text-left">申請日時</th>
                        <th scope="col" class="px-4 py-3 text-left">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($correctionRequests as $correctionRequest)
                        @php
                            $detailUrl = auth()->user()->role === 'admin'
                                ? route('admin.correction_requests.show', ['attendance_correct_request_id' => $correctionRequest->id])
                                : route('attendance.detail.show', ['id' => $correctionRequest->attendance_id]);
                        @endphp
                        <tr class="text-sm">
                            <td>
                                {{ $correctionRequest->status === 'pending' ? '承認待ち' : '承認済み' }}
                            </td>
                            <td>{{ $correctionRequest->user->name }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('Y/m/d') }}
                            </td>
                            <td>{{ $correctionRequest->requested_note }}</td>
                            <td>{{ $correctionRequest->created_at->format('Y/m/d') }}</td>
                            <td>
                                <a href="{{ $detailUrl }}" class="font-bold text-black">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500">
                                申請データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
