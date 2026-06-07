@extends('layouts.app')

@section('title', '管理者ログイン')

@section('content')
    <div class="auth-page">
        <div class="auth-panel">
            <h1 class="auth-title">管理者ログイン</h1>

            <form method="POST" action="/login">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" class="form-input" type="text" name="email" value="{{ old('email') }}" autofocus>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">パスワード</label>
                    <input id="password" class="form-input" type="password" name="password">
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="auth-submit">管理者ログインする</button>
            </form>
        </div>
    </div>
@endsection
