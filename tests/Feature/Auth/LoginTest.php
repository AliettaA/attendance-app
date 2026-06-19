<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required_when_logging_in(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'user',
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required_when_logging_in(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'user',
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_error_is_shown_when_login_information_does_not_match(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'user',
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
