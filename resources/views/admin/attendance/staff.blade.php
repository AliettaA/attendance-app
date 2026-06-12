@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('content')
    <main class="page-container">
        <h1 class="page-title">{{ $user->name }}さんの勤怠</h1>

        <div class="mb-6 flex items-center justify-between rounded bg-white px-6 py-4">
            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $previousMonth]) }}" class="font-semibold text-sm text-gray-500 hover:text-gray-700">←前月</a>
            <p class="flex items-center gap-2 text-lg font-bold">
                <x-icons.calendar class="h-6 w-6 text-gray-800" />
                {{ $month->format('Y年m月') }}
            </p>
            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}" class="font-semibold text-sm text-gray-500 hover:text-gray-700">翌月→</a>
        </div>

        <div class="table-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">日付</th>
                        <th class="px-4 py-3 text-left">出勤</th>
                        <th class="px-4 py-3 text-left">退勤</th>
                        <th class="px-4 py-3 text-left">休憩</th>
                        <th class="px-4 py-3 text-left">合計</th>
                        <th class="px-4 py-3 text-left">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceRows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['clock_in'] }}</td>
                            <td>{{ $row['clock_out'] }}</td>
                            <td>{{ $row['break_time'] }}</td>
                            <td>{{ $row['work_time'] }}</td>
                            <td>
                                <a href="{{ $row['detail_url'] }}" class="font-bold text-black">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex justify-end">
            <a href="{{ route('admin.attendance.staff.csv', ['id' => $user->id, 'month' => $month->format('Y-m')]) }}"
                class="inline-flex h-12 min-w-[120px] items-center justify-center rounded bg-black px-6 font-semibold text-white hover:bg-gray-800">
                CSV出力
            </a>
        </div>
    </main>
@endsection
