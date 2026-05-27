@extends('layouts.app')

@section('title', 'マイ勤怠レポート')

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">マイ勤怠レポート</h1>
            <p class="mt-2 text-sm font-semibold text-gray-500">集計期間：{{ $report['period_label'] }}</p>
        </div>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-bold text-gray-900">基本サマリー</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-gray-500">総労働時間</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $report['summary']['total_work_time'] }}</p>
                </div>
                <div class="rounded bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-gray-500">総残業時間</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $report['summary']['total_overtime_time'] }}</p>
                </div>
                <div class="rounded bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-gray-500">平均労働時間 / 日</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $report['summary']['average_work_time'] }}</p>
                </div>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-bold text-gray-900">月次推移（過去6ヶ月）</h2>
            <div class="overflow-hidden rounded bg-white shadow-sm">
                <table class="w-full table-auto border-collapse">
                    <thead class="bg-gray-100 text-sm text-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left">月</th>
                            <th class="px-4 py-3 text-left">労働時間</th>
                            <th class="px-4 py-3 text-left">平均労働時間 / 日</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['monthly_reports'] as $monthlyReport)
                            <tr class="border-t text-sm">
                                <td class="px-4 py-4 font-semibold">{{ $monthlyReport['month'] }}</td>
                                <td class="px-4 py-4">{{ $monthlyReport['total_work_time'] }}</td>
                                <td class="px-4 py-4">{{ $monthlyReport['average_work_time'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="mb-2 text-lg font-bold text-gray-900">今月の異常検知</h2>
            <p class="mb-4 text-sm font-semibold text-gray-500">
                基準：開始時間{{ $report['standards']['start_time'] }} / 終業{{ $report['standards']['end_time'] }} / 長時間労働は1日{{ $report['standards']['long_work_time'] }}
            </p>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-gray-500">遅刻回数</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $report['anomalies']['late_count'] }}回</p>
                </div>
                <div class="rounded bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-gray-500">早退回数</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $report['anomalies']['early_leave_count'] }}回</p>
                </div>
                <div class="rounded bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-gray-500">長時間労働日数</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $report['anomalies']['long_work_count'] }}日</p>
                </div>
            </div>
        </section>
    </div>
@endsection
