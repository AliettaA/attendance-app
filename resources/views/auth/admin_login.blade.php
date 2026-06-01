@extends('layouts.app')

@section('title', '管理者ログイン')

@section('content')
    <div class="auth-page">
        <div class="auth-panel">
            <h1 class="auth-title">管理者ログイン</h1>

            @if ($errors->any())
                <div class="auth-error-box">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @else
                <div class="auth-error-space"></div>
            @endif

            <form method="POST" action="/login">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">パスワード</label>
                    <input id="password" class="form-input" type="password" name="password" required>
                </div>

                <button type="submit" class="auth-submit">管理者ログインする</button>
            </form>
        </div>
    </div>
@endsection
