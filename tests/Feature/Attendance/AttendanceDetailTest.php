<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attendance_detail_shows_login_user_attendance_information(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:00:00',
            'clock_out_at' => '2026-06-05 18:00:00',
            'status' => 'finished',
            'note' => '通常勤務',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-05 12:00:00',
            'break_end_at' => '2026-06-05 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail.show', ['id' => $attendance->id]));

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('2026年');
        $response->assertSee('6月 5日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
