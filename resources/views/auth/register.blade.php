@extends('layouts.app')

@section('title', '会員登録')

@section('content')
    <div class="auth-page">
        <div class="auth-panel">
            <h1 class="auth-title">会員登録</h1>

            @if ($errors->any())
                <div class="auth-error-box">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @else
                <div class="auth-error-space"></div>
            @endif

            <form method="POST" action="/register">
                @csrf

                <div class="form-group">
                    <label for="name" class="form-label">名前</label>
                    <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">パスワード</label>
                    <input id="password" class="form-input" type="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">確認用パスワード</label>
                    <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required>
                </div>

                <button type="submit" class="auth-submit">登録する</button>
            </form>

            <a href="/login" class="auth-link">ログインはこちら</a>
        </div>
    </div>
@endsection
