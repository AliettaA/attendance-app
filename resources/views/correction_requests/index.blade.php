@extends('layouts.app')

@section('title', '申請一覧')

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-10">
        <h1 class="mb-8 text-2xl font-bold text-gray-900">申請一覧</h1>

        <div class="mb-6 flex border-b border-gray-300">
            <a href="/stamp_correction_request/list?status=pending"
                class="px-8 py-3 text-sm font-semibold {{ $status === 'pending' ? 'border-b-2 border-black text-black' : 'text-gray-500' }}">
                承認待ち
            </a>
            <a href="/stamp_correction_request/list?status=approved"
                class="px-8 py-3 text-sm font-semibold {{ $status === 'approved' ? 'border-b-2 border-black text-black' : 'text-gray-500' }}">
                承認済み
            </a>
        </div>

        <div class="overflow-hidden rounded bg-white shadow-sm">
            <table class="w-full table-auto border-collapse">
                <thead class="bg-gray-100 text-sm text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">状態</th>
                        <th class="px-4 py-3 text-left">名前</th>
                        <th class="px-4 py-3 text-left">対象日</th>
                        <th class="px-4 py-3 text-left">申請理由</th>
                        <th class="px-4 py-3 text-left">申請日時</th>
                        <th class="px-4 py-3 text-left">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($correctionRequests as $correctionRequest)
                        <tr class="border-t text-sm">
                            <td class="px-4 py-4">
                                {{ $correctionRequest->status === 'pending' ? '承認待ち' : '承認済み' }}
                            </td>
                            <td class="px-4 py-4">{{ $correctionRequest->user->name }}</td>
                            <td class="px-4 py-4">
                                {{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-4">{{ $correctionRequest->requested_note }}</td>
                            <td class="px-4 py-4">{{ $correctionRequest->created_at->format('Y/m/d') }}</td>
                            <td class="px-4 py-4">
                                <a href="/attendance/detail/{{ $correctionRequest->attendance_id }}" class="font-semibold text-blue-600 hover:text-blue-800">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                申請データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
