<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_unknown_email_shows_login_error(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => 'wrong-admin@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_wrong_password_shows_login_error(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
