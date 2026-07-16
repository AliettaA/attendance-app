@extends('layouts.app')

@section('title', '修正申請承認')

@section('content')
    <main class="page-container">
        <h1 class="page-title">勤怠詳細</h1>

        <div class="detail-panel">
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value">{{ $correctionRequestView['user_name'] }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-value detail-date-row">
                    <span>{{ $correctionRequestView['work_year'] }}</span>
                    <span>{{ $correctionRequestView['work_date'] }}</span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-input-row detail-approve-time-row">
                    <span class="time-value-start">{{ $correctionRequestView['clock_in'] }}</span>
                    <span class="time-separator">〜</span>
                    <span class="time-value-end">{{ $correctionRequestView['clock_out'] }}</span>
                </div>
            </div>

            @foreach ($correctionRequestView['break_rows'] as $index => $breakRow)
                <div class="detail-row">
                    <div class="detail-label">休憩{{ $index + 1 }}</div>
                    <div class="detail-input-row detail-approve-time-row">
                        <span class="time-value-start">{{ $breakRow['start'] }}</span>
                        <span class="time-separator">〜</span>
                        <span class="time-value-end">{{ $breakRow['end'] }}</span>
                    </div>
                </div>
            @endforeach

            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value">{{ $correctionRequestView['note'] }}</div>
            </div>

        </div>

        <div class="mt-8 text-right">
            @if ($correctionRequestView['status'] === 'pending')
                <form method="POST"
                    action="{{ route('admin.correction_requests.approve', ['attendance_correct_request_id' => $correctionRequestView['id']]) }}">
                    @csrf
                    <button type="submit" class="btn-action">承認</button>
                </form>
            @else
                <button type="button" class="btn-action-muted" disabled>修正済み</button>
            @endif
        </div>
    </main>
@endsection
