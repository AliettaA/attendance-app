@extends('layouts.app')

@section('title', '勤怠登録')

@section('content')
    <main class="attendance-register-page">
        <div class="attendance-register-panel">
            @php
                $status = $attendance?->status;
                $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                $today = now();
            @endphp

            <p class="mb-10 inline-flex h-[40px] w-[140px] items-center justify-center rounded-full bg-gray-300 text-lg font-semibold text-gray-600 shadow">
                @switch($status)
                    @case(null)
                        勤務外
                    @break

                    @case('working')
                        出勤中
                    @break

                    @case('on_break')
                        休憩中
                    @break

                    @case('finished')
                        退勤済
                    @break
                @endswitch
            </p>

            <p class="mb-8 text-[40px] text-gray-700">
                {{ $today->format('Y年m月d日') }}（{{ $weekdays[$today->dayOfWeek] }}）
            </p>

            <p class="attendance-current-time">
                {{ $today->format('H:i') }}
            </p>

            <div class="flex flex-wrap items-center justify-center gap-4">
                @switch($status)
                    @case(null)
                        <form method="POST" action="{{ route('attendance.clock_in') }}">
                            @csrf
                            <button type="submit" class="btn-primary">出勤</button>
                        </form>
                    @break

                    @case('working')
                        <form method="POST" action="{{ route('attendance.clock_out') }}">
                            @csrf
                            <button type="submit" class="btn-primary">退勤</button>
                        </form>

                        <form method="POST" action="{{ route('attendance.break_start') }}">
                            @csrf
                            <button type="submit" class="btn-primary-white">休憩入</button>
                        </form>
                    @break

                    @case('on_break')
                        <form method="POST" action="{{ route('attendance.break_end') }}">
                            @csrf
                            <button type="submit" class="btn-primary-white">休憩戻</button>
                        </form>
                    @break

                    @case('finished')
                        <p class="text-lg font-semibold">お疲れ様でした。</p>
                    @break
                @endswitch
            </div>
        </div>
    </main>
@endsection
