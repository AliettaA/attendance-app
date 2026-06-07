<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠管理アプリ')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <header class="bg-black shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="/attendance">
                <img src="{{ asset('images/logo.png') }}" alt="勤怠管理アプリ" class="h-8">
            </a>

            @auth
                @if (auth()->user()->role === 'admin')
                    <nav class="flex items-center gap-8 text-sm font-semibold text-white">
                        <a href="/admin/attendance/list" class="hover:text-gray-500">勤怠一覧</a>
                        <a href="/admin/staff/list" class="hover:text-gray-500">スタッフ一覧</a>
                        <a href="/stamp_correction_request/list" class="hover:text-gray-500">申請一覧</a>
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" class="font-semibold hover:text-gray-500">
                                ログアウト
                            </button>
                        </form>
                    </nav>
                @else
                    <nav class="flex items-center gap-8 text-sm font-semibold text-white">
                        <a href="/attendance" class="hover:text-gray-500">勤怠</a>
                        <a href="/attendance/list" class="hover:text-gray-500">勤怠一覧</a>
                        <a href="/stamp_correction_request/list" class="hover:text-gray-500">申請</a>
                        <a href="/attendance/report" class="hover:text-gray-500">レポート</a>
                        <form method="POST" action="/logout">
                            @csrf
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
