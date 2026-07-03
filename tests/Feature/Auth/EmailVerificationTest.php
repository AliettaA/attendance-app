<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/home');

        $user = User::where('email', 'user@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_notice_page_has_mailhog_link(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('認証はこちらから')
            ->assertSee('http://localhost:8025', false);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_is_redirected_to_attendance_page(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'user',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('attendance.index', ['verified' => 1]));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
