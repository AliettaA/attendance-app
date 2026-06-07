@extends('layouts.app')

@section('title', '管理者 勤怠一覧')

@section('content')
    <main class="page-container">
        <h1 class="page-title mb-8">{{ $date->format('Y年n月j日') }}の勤怠</h1>

        <div class="mb-6 flex items-center justify-between rounded bg-white px-6 py-4 shadow">
            <a href="/admin/attendance/list?date={{ $previousDate }}" class="font-semibold text-gray-500 hover:text-gray-700">←前日</a>

            <p class="flex items-center gap-2 text-lg font-bold">
                <svg class="h-6 w-6 text-gray-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="17" rx="2" stroke="currentColor" stroke-width="2" />
                    <path d="M3 9h18" stroke="currentColor" stroke-width="2" />
                    <path d="M8 2v4M16 2v4" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path d="M7 12h2M11 12h2M15 12h2M7 16h2M11 16h2M15 16h2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                {{ $date->format('Y年m月d日') }}
            </p>

            <a href="/admin/attendance/list?date={{ $nextDate }}" class="font-semibold text-gray-500 hover:text-gray-700">翌日→</a>
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
                    @forelse ($users as $user)
                        @php
                            $attendance = $user->attendances->first();
                            $breakMinutes = $attendance?->breakTimes->sum(function ($breakTime) {
                                if (! $breakTime->break_start_at || ! $breakTime->break_end_at) {
                                    return 0;
                                }

                                return \Carbon\Carbon::parse($breakTime->break_start_at)
                                    ->diffInMinutes(\Carbon\Carbon::parse($breakTime->break_end_at));
                            });
                            $workMinutes = 0;

                            if ($attendance?->clock_in_at && $attendance?->clock_out_at) {
                                $workMinutes = \Carbon\Carbon::parse($attendance->clock_in_at)
                                    ->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_out_at)) - $breakMinutes;
                            }
                        @endphp

                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $attendance?->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '' }}</td>
                            <td>{{ $attendance?->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '' }}</td>
                            <td>{{ $attendance ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '' }}</td>
                            <td>{{ $attendance ? sprintf('%d:%02d', intdiv(max($workMinutes, 0), 60), max($workMinutes, 0) % 60) : '' }}</td>
                            <td>
                                @if ($attendance)
                                    <a href="/admin/attendance/{{ $attendance->id }}" class="font-bold text-black">詳細</a>
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
