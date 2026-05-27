@extends('layouts.app')

@section('title', '勤怠詳細')

@section('content')
    <main class="mx-auto max-w-3xl px-6 py-10">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-2xl font-bold">勤怠詳細</h1>
        </div>

        @if ($pendingCorrectionRequest)
            <div class="mb-6 rounded bg-yellow-100 px-4 py-3 text-yellow-800">
                承認待ちのため修正できません。
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded bg-red-100 px-4 py-3 text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/attendance/detail/{{ $attendance->id }}">
            @csrf

            <div class="overflow-hidden rounded bg-white shadow">
                <div class="grid grid-cols-3 border-b">
                    <div class="bg-gray-100 px-4 py-4 font-semibold">名前</div>
                    <div class="col-span-2 px-4 py-4">{{ $attendance->user->name }}</div>
                </div>

                <div class="grid grid-cols-3 border-b">
                    <div class="bg-gray-100 px-4 py-4 font-semibold">日付</div>
                    <div class="col-span-2 px-4 py-4">
                        {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年m月d日') }}
                    </div>
                </div>

                <div class="grid grid-cols-3 border-b">
                    <div class="bg-gray-100 px-4 py-4 font-semibold">出勤・退勤</div>
                    <div class="col-span-2 flex items-center gap-4 px-4 py-4">
                        <input type="time" name="clock_in_at"
                            value="{{ old('clock_in_at', $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '') }}"
                            class="rounded border px-3 py-2" @disabled($pendingCorrectionRequest)>
                        <span>〜</span>
                        <input type="time" name="clock_out_at"
                            value="{{ old('clock_out_at', $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '') }}"
                            class="rounded border px-3 py-2" @disabled($pendingCorrectionRequest)>
                    </div>
                </div>

                @php
                    $breakRows = $attendance->breakTimes->values();
                @endphp

                @for ($index = 0; $index < 2; $index++)
                    @php
                        $breakTime = $breakRows->get($index);
                    @endphp

                    <div class="grid grid-cols-3 border-b">
                        <div class="bg-gray-100 px-4 py-4 font-semibold">
                            休憩{{ $index + 1 }}
                        </div>
                        <div class="col-span-2 flex items-center gap-4 px-4 py-4">
                            <input type="hidden" name="breaks[{{ $index }}][original_break_time_id]"
                                value="{{ old('breaks.' . $index . '.original_break_time_id', $breakTime?->id) }}">
                            <input type="time" name="breaks[{{ $index }}][start]"
                                value="{{ old('breaks.' . $index . '.start', $breakTime?->break_start_at ? \Carbon\Carbon::parse($breakTime->break_start_at)->format('H:i') : '') }}"
                                class="rounded border px-3 py-2" @disabled($pendingCorrectionRequest)>
                            <span>〜</span>
                            <input type="time" name="breaks[{{ $index }}][end]"
                                value="{{ old('breaks.' . $index . '.end', $breakTime?->break_end_at ? \Carbon\Carbon::parse($breakTime->break_end_at)->format('H:i') : '') }}"
                                class="rounded border px-3 py-2" @disabled($pendingCorrectionRequest)>
                        </div>
                    </div>
                @endfor

                <div class="grid grid-cols-3 border-b">
                    <div class="bg-gray-100 px-4 py-4 font-semibold">備考</div>
                    <div class="col-span-2 px-4 py-4">
                        <textarea name="note" rows="4" class="w-full rounded border px-3 py-2"
                            @disabled($pendingCorrectionRequest)>{{ old('note', $attendance->note) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-right">
            <button type="submit" class="btn-primary" @disabled($pendingCorrectionRequest)>
                修正
            </button>
            </div>
        </form>
    </main>
@endsection
