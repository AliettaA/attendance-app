@extends('layouts.app')

@section('title', '管理者 勤怠詳細')

@section('content')
    <main class="page-container">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="page-title">勤怠詳細</h1>
        </div>

        <form method="POST" action="{{ $attendance->exists ? '/admin/attendance/' . $attendance->id : '/admin/attendance/staff/' . $attendance->user_id . '/detail/create' }}">
            @csrf
            @unless ($attendance->exists)
                <input type="hidden" name="work_date" value="{{ \Carbon\Carbon::parse($attendance->work_date)->toDateString() }}">
            @endunless

            <div class="detail-panel">
                <div class="detail-row">
                    <div class="detail-label">名前</div>
                    <div class="detail-value">{{ $attendance->user->name }}</div>
                </div>

                @php
                    $workDate = \Carbon\Carbon::parse($attendance->work_date);
                @endphp

                <div class="detail-row">
                    <div class="detail-label">日付</div>
                    <div class="detail-input-row">
                        <span class="inline-block w-28">{{ $workDate->format('Y年') }}</span>
                        <span class="inline-block w-28">{{ $workDate->format('n月 j日') }}</span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">出勤・退勤</div>
                    <div class="detail-input-row">
                        <input type="time" name="clock_in_at"
                            value="{{ old('clock_in_at', $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '') }}"
                            class="time-input" @disabled($pendingCorrectionRequest)>
                        <span>〜</span>
                        <input type="time" name="clock_out_at"
                            value="{{ old('clock_out_at', $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '') }}"
                            class="time-input" @disabled($pendingCorrectionRequest)>
                        <p class="detail-error">
                            @error('clock_in_at')
                                {{ $message }}
                            @enderror
                            @error('clock_out_at')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>

                @php
                    $breakRows = $attendance->breakTimes->values();
                    $breakInputCount = $breakRows->count() + 1;
                @endphp

                @for ($index = 0; $index < $breakInputCount; $index++)
                    @php
                        $breakTime = $breakRows->get($index);
                    @endphp

                    <div class="detail-row">
                        <div class="detail-label">
                            休憩{{ $index + 1 }}
                        </div>
                        <div class="detail-input-row">
                            <input type="hidden" name="breaks[{{ $index }}][original_break_time_id]"
                                value="{{ old('breaks.' . $index . '.original_break_time_id', $breakTime?->id) }}">
                            <input type="time" name="breaks[{{ $index }}][start]"
                                value="{{ old('breaks.' . $index . '.start', $breakTime?->break_start_at ? \Carbon\Carbon::parse($breakTime->break_start_at)->format('H:i') : '') }}"
                                class="time-input" @disabled($pendingCorrectionRequest)>
                            <span>〜</span>
                            <input type="time" name="breaks[{{ $index }}][end]"
                                value="{{ old('breaks.' . $index . '.end', $breakTime?->break_end_at ? \Carbon\Carbon::parse($breakTime->break_end_at)->format('H:i') : '') }}"
                                class="time-input" @disabled($pendingCorrectionRequest)>
                            <p class="detail-error">
                                @error('breaks.' . $index . '.start')
                                    {{ $message }}
                                @enderror
                                @error('breaks.' . $index . '.end')
                                    {{ $message }}
                                @enderror
                                @if ($index === $breakInputCount - 1)
                                    @error('breaks')
                                        {{ $message }}
                                    @enderror
                                @endif
                            </p>
                        </div>
                    </div>
                @endfor

                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value">
                        <textarea name="note" rows="4" class="w-full rounded border px-3 py-2 font-bold text-black"
                            @disabled($pendingCorrectionRequest)>{{ old('note', $attendance->note) }}</textarea>
                        <p class="detail-error">
                            @error('note')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-right">
                @if ($pendingCorrectionRequest)
                    <p class="font-semibold text-red-600">※承認待ちのため修正はできません。</p>
                @else
                    <button type="submit" class="btn-action">
                        修正
                    </button>
                @endif
            </div>
        </form>
    </main>
@endsection
