<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_clock_in_starts_work(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('出勤');

        $this->actingAs($user)
            ->post(route('attendance.clock_in'))
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-06-19',
            'clock_in_at' => '2026-06-19 09:00:00',
            'status' => 'working',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('出勤中');
    }

    public function test_clock_in_button_is_hidden_after_finished(): void
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

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('退勤済')
            ->assertDontSee('出勤');
    }

    public function test_clock_in_time_is_shown_on_list(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('attendance.clock_in'))
            ->assertRedirect(route('attendance.index'));

        $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('06/19（金）')
            ->assertSee('09:00');
    }
}
