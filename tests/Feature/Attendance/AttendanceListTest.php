<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_records_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:00:00',
            'clock_out_at' => '2026-06-05 18:00:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-06']));

        $response->assertOk();
        $response->assertSee('06/05（金）');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_current_month_is_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertOk();
        $response->assertSee('2026年06月');
    }

    public function test_invalid_month_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.list'))
            ->get(route('attendance.list', ['month' => 'invalid-month']));

        $response->assertRedirect(route('attendance.list'));
        $response->assertSessionHasErrors('month');
    }

    public function test_previous_month_records_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-10',
            'clock_in_at' => '2026-05-10 09:00:00',
            'clock_out_at' => '2026-05-10 18:00:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-05']));

        $response->assertOk();
        $response->assertSee('2026年05月');
        $response->assertSee('05/10（日）');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_next_month_records_are_shown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in_at' => '2026-07-10 09:00:00',
            'clock_out_at' => '2026-07-10 18:00:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-07']));

        $response->assertOk();
        $response->assertSee('2026年07月');
        $response->assertSee('07/10（金）');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_detail_link_opens_detail(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 09:00:00'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:00:00',
            'clock_out_at' => '2026-06-05 18:00:00',
            'status' => 'finished',
            'note' => 'テスト用の勤怠',
        ]);

        $detailUrl = route('attendance.detail.show', ['id' => $attendance->id]);

        $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee($detailUrl, false);

        $this->actingAs($user)
            ->get($detailUrl)
            ->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('2026年')
            ->assertSee('6月 5日')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }
}
