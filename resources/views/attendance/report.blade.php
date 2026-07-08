@extends('layouts.app')

@section('title', 'マイ勤怠レポート')

@section('content')
    <main class="min-h-[calc(100vh-72px)] bg-white">
        <div class="page-container">
            <div class="mb-5">
                <h1 class="text-[26px] font-semibold">マイ勤怠レポート</h1>
                <p class="mt-2 text-sm font-semibold text-black">過去6ヶ月の勤怠データから集計しています</p>
            </div>

            <section class="mb-6">
                <h2 class="mb-2 text-sm font-bold text-black">基本サマリー</h2>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="report-card">
                        <p class="report-card-label">総労働時間</p>
                        <p class="report-card-value">{{ $report['summary']['total_work_time'] }}</p>
                    </div>
                    <div class="report-card">
                        <p class="report-card-label">総残業時間</p>
                        <p class="report-card-value">{{ $report['summary']['total_overtime_time'] }}</p>
                    </div>
                    <div class="report-card">
                        <p class="report-card-label">平均労働時間 / 日</p>
                        <p class="report-card-value">{{ $report['summary']['average_work_time'] }}</p>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="mb-2 text-sm font-bold text-gray-900">月次推移（過去6ヶ月）</h2>
                <div class="table-panel">
                    <table class="data-table report-monthly-table">
                        <thead class="text-sm">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left">月</th>
                                <th scope="col" class="px-4 py-3 text-right">労働時間</th>
                                <th scope="col" class="px-4 py-3 text-right">残業時間</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['monthly_reports'] as $monthlyReport)
                                <tr class="text-sm">
                                    <td class="font-semibold">{{ $monthlyReport['month'] }}</td>
                                    <td class="text-right">{{ $monthlyReport['total_work_time'] }}</td>
                                    <td class="text-right">{{ $monthlyReport['total_overtime_time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="mb-2 text-sm font-bold text-gray-900">今月の異常検知</h2>
                <p class="mb-4 text-[14px] font-semibold text-gray-500">
                    基準：開始時間{{ $report['standards']['start_time'] }} / 終業{{ $report['standards']['end_time'] }} / 長時間労働は1日{{ $report['standards']['long_work_time'] }}
                </p>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="report-card">
                        <p class="report-card-label">遅刻回数</p>
                        <p class="report-card-value">{{ $report['anomalies']['late_count'] }}回</p>
                    </div>
                    <div class="report-card">
                        <p class="report-card-label">早退回数</p>
                        <p class="report-card-value">{{ $report['anomalies']['early_leave_count'] }}回</p>
                    </div>
                    <div class="report-card">
                        <p class="report-card-label">長時間労働日数</p>
                        <p class="report-card-value">{{ $report['anomalies']['long_work_count'] }}日</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection
