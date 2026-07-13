@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('content')
    <main class="page-container staff-list-page">
        <h1 class="page-title mb-8">スタッフ一覧</h1>

        <div class="table-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left">名前</th>
                        <th scope="col" class="px-4 py-3 text-left">メールアドレス</th>
                        <th scope="col" class="px-4 py-3 text-left">月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffMembers as $staff)
                        <tr>
                            <td>{{ $staff->name }}</td>
                            <td>{{ $staff->email }}</td>
                            <td>
                                <a href="{{ route('admin.attendance.staff', ['id' => $staff->id]) }}" class="font-bold text-black">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-500">スタッフが登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
