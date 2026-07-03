<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_date_and_time_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:23:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('2026年06月19日（金）');
        $response->assertSee('09:23');
    }

    public function test_off_duty_status_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('勤務外');
    }

    public function test_working_status_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in_at' => now(),
            'status' => 'working',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('出勤中');
    }

    public function test_on_break_status_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in_at' => today()->setTime(9, 0),
            'status' => 'on_break',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('休憩中');
    }

    public function test_finished_status_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 18:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in_at' => today()->setTime(9, 0),
            'clock_out_at' => now(),
            'status' => 'finished',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('退勤済');
    }
}
