<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_break_start_button_works_and_status_becomes_on_break(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $attendance = $this->createWorkingAttendance($user);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('休憩入');

        $this->actingAs($user)
            ->post(route('attendance.break_start'))
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-19 12:00:00',
            'break_end_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('休憩中');
    }

    public function test_break_can_be_started_multiple_times_in_one_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::parse('2026-06-19 13:00:00'));
        $this->actingAs($user)->post(route('attendance.break_end'));

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    public function test_break_end_button_works_and_status_becomes_working(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $attendance = $this->createWorkingAttendance($user);

        $this->actingAs($user)->post(route('attendance.break_start'));

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('休憩戻');

        Carbon::setTestNow(Carbon::parse('2026-06-19 13:00:00'));

        $this->actingAs($user)
            ->post(route('attendance.break_end'))
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-19 12:00:00',
            'break_end_at' => '2026-06-19 13:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('出勤中');
    }

    public function test_break_end_can_be_used_multiple_times_in_one_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::parse('2026-06-19 13:00:00'));
        $this->actingAs($user)->post(route('attendance.break_end'));

        Carbon::setTestNow(Carbon::parse('2026-06-19 15:00:00'));
        $this->actingAs($user)->post(route('attendance.break_start'));

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('休憩中')
            ->assertSee('休憩戻');
    }

    public function test_break_time_is_shown_on_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $attendance = $this->createWorkingAttendance($user);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-19 12:00:00',
            'break_end_at' => '2026-06-19 13:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('06/19（金）')
            ->assertSee('1:00');
    }

    private function createWorkingAttendance(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in_at' => today()->setTime(9, 0),
            'status' => 'working',
        ]);
    }
}
