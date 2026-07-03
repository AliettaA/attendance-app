<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_required(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    public function test_email_is_required(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_must_be_at_least_eight_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    public function test_password_is_required(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_valid_input_creates_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/home');

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $this->assertTrue(Hash::check('password', User::where('email', 'user@example.com')->first()->password));
    }
}
