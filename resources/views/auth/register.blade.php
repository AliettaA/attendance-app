@extends('layouts.app')

@section('title', '会員登録')

@section('content')
    <div class="auth-page">
        <div class="auth-panel">
            <h1 class="auth-title">会員登録</h1>

            <form method="POST" action="{{ route('register') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="name" class="form-label">名前</label>
                    <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}">
                    <p class="form-error">
                        @error('name')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" class="form-input" type="text" name="email" value="{{ old('email') }}">
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

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">確認用パスワード</label>
                    <input id="password_confirmation" class="form-input" type="password" name="password_confirmation">
                    <p class="form-error">
                        @error('password_confirmation')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <button type="submit" class="auth-submit">登録する</button>
            </form>

            <a href="{{ route('login') }}" class="auth-link">ログインはこちら</a>
        </div>
    </div>
@endsection
