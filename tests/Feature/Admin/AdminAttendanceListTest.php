<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_daily_records_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user1 = User::factory()->create([
            'name' => '山田太郎',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $user2 = User::factory()->create([
            'name' => '佐藤花子',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-06-19',
            'clock_in_at' => '2026-06-19 09:00:00',
            'clock_out_at' => '2026-06-19 18:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => '2026-06-19 12:00:00',
            'break_end_at' => '2026-06-19 13:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-06-19',
            'clock_in_at' => '2026-06-19 10:00:00',
            'clock_out_at' => '2026-06-19 19:00:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['date' => '2026-06-19']));

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('佐藤花子');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('9:00');
    }

    public function test_current_date_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertSee('2026年6月19日の勤怠');
        $response->assertSee('2026年06月19日');
    }

    public function test_previous_day_records_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-18',
            'clock_in_at' => '2026-06-18 09:30:00',
            'clock_out_at' => '2026-06-18 18:30:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['date' => '2026-06-18']));

        $response->assertOk();
        $response->assertSee('2026年6月18日の勤怠');
        $response->assertSee('2026年06月18日');
        $response->assertSee('山田太郎');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
    }

    public function test_next_day_records_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-20',
            'clock_in_at' => '2026-06-20 08:45:00',
            'clock_out_at' => '2026-06-20 17:45:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['date' => '2026-06-20']));

        $response->assertOk();
        $response->assertSee('2026年6月20日の勤怠');
        $response->assertSee('2026年06月20日');
        $response->assertSee('山田太郎');
        $response->assertSee('08:45');
        $response->assertSee('17:45');
    }
}
