@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('content')
    <main class="page-container attendance-list-page">
        <h1 class="page-title">{{ $user->name }}さんの勤怠</h1>

        <div class="period-nav">
            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $previousMonth]) }}" class="period-nav-link">←前月</a>
            <p class="period-nav-title">
                <x-icons.calendar class="h-6 w-6 text-gray-800" />
                {{ $month->format('Y年m月') }}
            </p>
            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}" class="period-nav-link">翌月→</a>
        </div>

        <div class="table-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left">日付</th>
                        <th scope="col" class="px-4 py-3 text-left">出勤</th>
                        <th scope="col" class="px-4 py-3 text-left">退勤</th>
                        <th scope="col" class="px-4 py-3 text-left">休憩</th>
                        <th scope="col" class="px-4 py-3 text-left">合計</th>
                        <th scope="col" class="px-4 py-3 text-left">詳細</th>
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
                class="btn-action-compact">
                CSV出力
            </a>
        </div>
    </main>
@endsection
