<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('attendance.report'))
            ->assertRedirect(route('login'));
    }

    public function test_report_is_calculated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:30:00',
            'clock_out_at' => '2026-06-05 18:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => '2026-06-05 12:00:00',
            'break_end_at' => '2026-06-05 13:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-06',
            'clock_in_at' => '2026-06-06 09:00:00',
            'clock_out_at' => '2026-06-06 17:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => '2026-06-06 12:00:00',
            'break_end_at' => '2026-06-06 13:00:00',
        ]);

        $attendance3 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-10',
            'clock_in_at' => '2026-05-10 09:00:00',
            'clock_out_at' => '2026-05-10 12:00:00',
            'status' => 'finished',
        ]);

        User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ])->attendances()->create([
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:00:00',
            'clock_out_at' => '2026-06-05 23:00:00',
            'status' => 'finished',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.report'))
            ->assertOk()
            ->assertSee('マイ勤怠レポート')
            ->assertSee('総労働時間')
            ->assertSee('17h30m')
            ->assertSee('総残業時間')
            ->assertSee('0h00m')
            ->assertSee('平均労働時間 / 日')
            ->assertSee('5h50m')
            ->assertSee('2026年05月')
            ->assertSee('3h00m')
            ->assertSee('2026年06月')
            ->assertSee('14h30m')
            ->assertSee('遅刻回数')
            ->assertSee('1回')
            ->assertSee('早退回数')
            ->assertSee('1回')
            ->assertSee('長時間労働日数')
            ->assertSee('0日');
    }

    public function test_empty_report_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance.report'))
            ->assertOk()
            ->assertSee('総労働時間')
            ->assertSee('総残業時間')
            ->assertSee('平均労働時間 / 日')
            ->assertSee('0h00m')
            ->assertSee('2026年01月')
            ->assertSee('2026年06月')
            ->assertSee('遅刻回数')
            ->assertSee('0回')
            ->assertSee('早退回数')
            ->assertSee('0回')
            ->assertSee('長時間労働日数')
            ->assertSee('0日');
    }
}
