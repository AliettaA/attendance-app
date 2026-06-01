@extends('layouts.app')

@section('title', '修正申請承認')

@section('content')
    <main class="page-container">
        <h1 class="page-title mb-8">修正申請承認</h1>

        <div class="detail-panel">
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value">{{ $correctionRequest->user->name }}</div>
            </div>

            @php
                $workDate = \Carbon\Carbon::parse($correctionRequest->attendance->work_date);
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
                    <span class="inline-block w-28">{{ $correctionRequest->requested_clock_in_at ? \Carbon\Carbon::parse($correctionRequest->requested_clock_in_at)->format('H:i') : '' }}</span>
                    <span>〜</span>
                    <span class="inline-block w-28">{{ $correctionRequest->requested_clock_out_at ? \Carbon\Carbon::parse($correctionRequest->requested_clock_out_at)->format('H:i') : '' }}</span>
                </div>
            </div>

            @php
                $requestBreakRows = $correctionRequest->correctionRequestBreaks->values();
            @endphp

            @for ($index = 0; $index < 2; $index++)
                @php
                    $requestBreak = $requestBreakRows->get($index);
                @endphp

                <div class="detail-row">
                    <div class="detail-label">休憩{{ $index + 1 }}</div>
                    <div class="detail-input-row">
                        <span class="inline-block w-28">{{ $requestBreak?->requested_break_start_at ? \Carbon\Carbon::parse($requestBreak->requested_break_start_at)->format('H:i') : '' }}</span>
                        <span>〜</span>
                        <span class="inline-block w-28">{{ $requestBreak?->requested_break_end_at ? \Carbon\Carbon::parse($requestBreak->requested_break_end_at)->format('H:i') : '' }}</span>
                    </div>
                </div>
            @endfor

            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value">{{ $correctionRequest->requested_note }}</div>
            </div>

        </div>

        <div class="mt-6 text-right">
            @if ($correctionRequest->status === 'pending')
                <form method="POST" action="/stamp_correction_request/approve/{{ $correctionRequest->id }}">
                    @csrf
                    <button type="submit" class="btn-primary">承認</button>
                </form>
            @else
                <button type="button" class="btn-primary cursor-not-allowed bg-gray-400" disabled>修正済み</button>
            @endif
        </div>
    </main>
@endsection
