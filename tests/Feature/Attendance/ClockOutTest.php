<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_clock_out_button_works_and_status_becomes_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 18:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in_at' => today()->setTime(9, 0),
            'status' => 'working',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('退勤');

        $this->actingAs($user)
            ->post(route('attendance.clock_out'))
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out_at' => '2026-06-19 18:00:00',
            'status' => 'finished',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('退勤済');
    }

    public function test_clock_out_time_is_shown_on_attendance_list(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $this->actingAs($user)
            ->post(route('attendance.clock_in'))
            ->assertRedirect(route('attendance.index'));

        Carbon::setTestNow(Carbon::parse('2026-06-19 18:00:00'));

        $this->actingAs($user)
            ->post(route('attendance.clock_out'))
            ->assertRedirect(route('attendance.index'));

        $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('06/19（金）')
            ->assertSee('18:00');
    }
}
