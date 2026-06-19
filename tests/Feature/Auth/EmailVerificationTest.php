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

    public function test_verification_email_is_sent_after_registration(): void
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

    public function test_verification_notice_page_has_link_to_mailhog(): void
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

    public function test_user_is_redirected_to_attendance_page_after_email_verification(): void
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
