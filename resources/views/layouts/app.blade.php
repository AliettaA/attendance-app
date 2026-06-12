<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠管理アプリ')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <header class="bg-black">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            @php
                $logoUrl = route('login');

                if (auth()->check()) {
                    $logoUrl = auth()->user()->role === 'admin'
                        ? route('admin.attendance.index')
                        : route('attendance.index');
                }
            @endphp

            <a href="{{ $logoUrl }}">
                <img src="{{ asset('images/logo.png') }}" alt="勤怠管理アプリ" class="h-6">
            </a>

            @auth
                @if (auth()->user()->role === 'admin')
                    <nav class="flex items-center gap-8 text-sm font-semibold text-white">
                        <a href="{{ route('admin.attendance.index') }}" class="hover:text-gray-500">勤怠一覧</a>
                        <a href="{{ route('admin.staff.index') }}" class="hover:text-gray-500">スタッフ一覧</a>
                        <a href="{{ route('correction_requests.index') }}" class="hover:text-gray-500">申請一覧</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <input type="hidden" name="logout_role" value="admin">
                            <button type="submit" class="font-semibold hover:text-gray-500">
                                ログアウト
                            </button>
                        </form>
                    </nav>
                @else
                    <nav class="flex items-center gap-8 text-sm font-semibold text-white">
                        <a href="{{ route('attendance.index') }}" class="hover:text-gray-500">勤怠</a>
                        <a href="{{ route('attendance.list') }}" class="hover:text-gray-500">勤怠一覧</a>
                        <a href="{{ route('correction_requests.index') }}" class="hover:text-gray-500">申請</a>
                        <a href="{{ route('attendance.report') }}" class="hover:text-gray-500">レポート</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <input type="hidden" name="logout_role" value="user">
                            <button type="submit" class="font-semibold hover:text-gray-500">
                                ログアウト
                            </button>
                        </form>
                    </nav>
                @endif
            @endauth
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @if (session('status'))
        <div class="flash-message">
            {{ session('status') === 'verification-link-sent' ? '認証メールを再送信しました。' : session('status') }}
        </div>
    @endif
</body>

</html>
