@extends('layouts.app')

@section('title', '勤怠一覧')

@section('content')
<main class="mx-auto max-w-5xl px-6 py-10">
        <h1 class="mb-8 text-2xl font-bold">勤怠一覧</h1>

        <div class="mb-6 flex items-center justify-between rounded bg-white px-6 py-4 shadow">
            <a href="/attendance/list?month={{ $previousMonth }}" class="font-semibold text-blue-600">
                前月
            </a>

            <p class="text-lg font-bold">
                {{ $month->format('Y年m月') }}
            </p>

            <a href="/attendance/list?month={{ $nextMonth }}" class="font-semibold text-blue-600">
                翌月
            </a>
        </div>

        <div class="overflow-hidden rounded bg-white shadow">
            <table class="w-full table-auto border-collapse">
                <thead class="bg-gray-200">
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
                    @forelse ($attendances as $attendance)
                        @php
                            $breakMinutes = $attendance->breakTimes->sum(function ($breakTime) {
                                if (is_null($breakTime->break_end_at)) {
                                    return 0;
                                }

                                return \Carbon\Carbon::parse($breakTime->break_start_at)
                                    ->diffInMinutes(\Carbon\Carbon::parse($breakTime->break_end_at));
                            });

                            $workMinutes = 0;

                            if ($attendance->clock_in_at && $attendance->clock_out_at) {
                                $workMinutes = \Carbon\Carbon::parse($attendance->clock_in_at)
                                    ->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_out_at)) - $breakMinutes;
                            }

                            $breakHours = floor($breakMinutes / 60);
                            $breakRemainderMinutes = $breakMinutes % 60;
                            $workHours = floor($workMinutes / 60);
                            $workRemainderMinutes = $workMinutes % 60;
                        @endphp

                        <tr class="border-t">
                            <td class="px-4 py-3">
                                {{ \Carbon\Carbon::parse($attendance->work_date)->format('m/d') }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ sprintf('%d:%02d', $breakHours, $breakRemainderMinutes) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ sprintf('%d:%02d', $workHours, $workRemainderMinutes) }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="/attendance/detail/{{ $attendance->id }}" class="font-semibold text-blue-600">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                勤怠データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
