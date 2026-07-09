@extends('layouts.app')

@section('title', '管理者 勤怠一覧')

@section('content')
    <main class="page-container">
        <h1 class="page-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>

        <div class="period-nav">
            <a href="{{ route('admin.attendance.index', ['date' => $previousDate]) }}" class="period-nav-link">←前日</a>

            <p class="period-nav-title">
                <x-icons.calendar class="h-6 w-6 text-gray-800" />
                {{ $date->format('Y年m月d日') }}
            </p>
            <a href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}" class="period-nav-link">翌日→</a>
        </div>

        <div class="table-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left">名前</th>
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
@endsection
