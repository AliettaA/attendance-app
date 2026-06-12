@extends('layouts.app')

@section('title', '勤怠登録')

@section('content')
        <main class="attendance-register-page">
            <div class="attendance-register-panel">
                    @php
    $status = $attendance?->status;
                    @endphp

                    <p class="mb-10 inline-flex h-[40px] w-[140px] items-center justify-center rounded-full bg-gray-300 text-lg font-semibold text-gray-600 shadow">
                        @if (is_null($attendance))
                            勤務外
                        @elseif ($status === 'working')
                            出勤中
                        @elseif ($status === 'on_break')
                            休憩中
                        @elseif ($status === 'finished')
                            退勤済
                        @endif
                    </p>

                    @php
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    $today = now();
                    @endphp

                    <p class="mb-8 text-[40px] text-gray-700">
                        {{ $today->format('Y年m月d日') }}（{{ $weekdays[$today->dayOfWeek] }}）
                    </p>

                    <p class="attendance-current-time">
                        {{ $today->format('H:i') }}
                    </p>

                    <div class="flex flex-wrap items-center justify-center gap-4">
                        @if (is_null($attendance))
                            <form method="POST" action="{{ route('attendance.clock_in') }}">
                                @csrf
                                <button type="submit" class="btn-primary">出勤</button>
                            </form>
                        @elseif ($status === 'working')
                            <form method="POST" action="{{ route('attendance.clock_out') }}">
                                @csrf
                                <button type="submit" class="btn-primary">退勤</button>
                            </form>

                            <form method="POST" action="{{ route('attendance.break_start') }}">
                                @csrf
                                <button type="submit" class="btn-primary-white">休憩入</button>
                            </form>

                        @elseif ($status === 'on_break')
                            <form method="POST" action="{{ route('attendance.break_end') }}">
                                @csrf
                                <button type="submit" class="btn-primary-white">休憩戻</button>
                            </form>
                        @elseif ($status === 'finished')
                            <p class="text-lg font-semibold">お疲れ様でした。</p>
                        @endif
                    </div>
            </div>
        </main>
@endsection
