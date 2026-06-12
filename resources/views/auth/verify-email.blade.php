@extends('layouts.app')

@section('title', 'メール認証')

@section('content')
    <div class="auth-page verify-email-page">
        <div class="width-720">
            <div class="text-center">
                <p class="font-bold leading-8 text-[24px] text-black">
                    登録していただいたメールアドレスに認証メールを送付しました。<br>
                    メール認証を完了してください。
                </p>
                <a href="http://localhost:8025" target="_blank" rel="noopener" class="verify-email-button">
                    認証はこちらから
                </a>

                <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="auth-link mx-auto mt-10">
                        認証メールを再送信する
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
