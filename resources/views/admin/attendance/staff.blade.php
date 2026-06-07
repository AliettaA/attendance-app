@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('content')
    <main class="page-container">
        <h1 class="page-title mb-8">{{ $user->name }}さんの勤怠</h1>

        <div class="mb-6 flex items-center justify-between rounded bg-white px-6 py-4 shadow">
            <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $previousMonth }}" class="font-semibold text-gray-500 hover:text-gray-700">←前月</a>
            <p class="text-lg font-bold">{{ $month->format('Y年m月') }}</p>
            <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $nextMonth }}" class="font-semibold text-gray-500 hover:text-gray-700">翌月→</a>
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
                    @foreach ($dates as $date)
                        @php
                            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                            $attendance = $attendances->get($date->toDateString());
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
                            <td>{{ $date->format('m/d') }}（{{ $weekdays[$date->dayOfWeek] }}）</td>
                            <td>{{ $attendance?->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '' }}</td>
                            <td>{{ $attendance?->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '' }}</td>
                            <td>{{ $attendance ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '' }}</td>
                            <td>{{ $attendance ? sprintf('%d:%02d', intdiv(max($workMinutes, 0), 60), max($workMinutes, 0) % 60) : '' }}</td>
                            <td>
                                <a href="{{ $attendance ? '/admin/attendance/' . $attendance->id : '/admin/attendance/staff/' . $user->id . '/detail/create?date=' . $date->toDateString() }}" class="font-bold text-black">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex justify-end">
            <a href="/admin/attendance/staff/{{ $user->id }}/csv?month={{ $month->format('Y-m') }}"
                class="inline-flex h-12 min-w-[120px] items-center justify-center rounded bg-black px-6 font-semibold text-white hover:bg-gray-800">
                CSV出力
            </a>
        </div>
    </main>
@endsection
