@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
    <div class="auth-page">
        <div class="auth-panel">
            <h1 class="auth-title">ログイン</h1>

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf
                <input type="hidden" name="login_type" value="user">

                <div class="form-group">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" class="form-input" type="text" name="email" value="{{ old('email') }}"
                        autofocus>
                    <p class="form-error">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">パスワード</label>
                    <input id="password" class="form-input" type="password" name="password">
                    <p class="form-error">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <button type="submit" class="auth-submit">ログインする</button>
            </form>

            <a href="{{ route('register') }}" class="auth-link">会員登録はこちら</a>
        </div>
    </div>
@endsection
