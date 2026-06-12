@extends('layouts.app')

@section('title', '管理者 勤怠一覧')

@section('content')
    <main class="page-container">
        <h1 class="page-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>

        <div class="mb-6 flex items-center justify-between rounded bg-white px-6 py-4">
            <a href="{{ route('admin.attendance.index', ['date' => $previousDate]) }}" class="font-semibold text-sm text-gray-500 hover:text-gray-700">←前日</a>

            <p class="flex items-center gap-2 text-lg font-bold">
                <x-icons.calendar class="h-6 w-6 text-gray-800" />
                {{ $date->format('Y年m月d日') }}
            </p>
            <a href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}" class="font-semibold text-sm text-gray-500 hover:text-gray-700">翌日→</a>
        </div>

        <div class="table-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">名前</th>
                        <th class="px-4 py-3 text-left">出勤</th>
                        <th class="px-4 py-3 text-left">退勤</th>
                        <th class="px-4 py-3 text-left">休憩</th>
                        <th class="px-4 py-3 text-left">合計</th>
                        <th class="px-4 py-3 text-left">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendanceRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['clock_in'] }}</td>
                            <td>{{ $row['clock_out'] }}</td>
                            <td>{{ $row['break_time'] }}</td>
                            <td>{{ $row['work_time'] }}</td>
                            <td>
                                @if ($row['detail_url'])
                                    <a href="{{ $row['detail_url'] }}" class="font-bold text-black">詳細</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500">勤怠データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
