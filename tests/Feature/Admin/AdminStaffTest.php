<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_staff_list_is_shown(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => '管理者ユーザー',
            'email' => 'admin-only@example.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.index'));

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
        $response->assertDontSee('管理者ユーザー');
        $response->assertDontSee('admin-only@example.com');
    }

    public function test_staff_records_are_shown(): void
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
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:00:00',
            'clock_out_at' => '2026-06-05 18:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-05 12:00:00',
            'break_end_at' => '2026-06-05 13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => '2026-06']));

        $response->assertOk();
        $response->assertSee('山田太郎さんの勤怠');
        $response->assertSee('2026年06月');
        $response->assertSee('06/05（金）');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    public function test_previous_month_records_are_shown(): void
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
            'work_date' => '2026-05-10',
            'clock_in_at' => '2026-05-10 09:30:00',
            'clock_out_at' => '2026-05-10 18:30:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => '2026-05']));

        $response->assertOk();
        $response->assertSee('2026年05月');
        $response->assertSee('05/10（日）');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
    }

    public function test_next_month_records_are_shown(): void
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
            'work_date' => '2026-07-10',
            'clock_in_at' => '2026-07-10 08:45:00',
            'clock_out_at' => '2026-07-10 17:45:00',
            'status' => 'finished',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => '2026-07']));

        $response->assertOk();
        $response->assertSee('2026年07月');
        $response->assertSee('07/10（金）');
        $response->assertSee('08:45');
        $response->assertSee('17:45');
    }

    public function test_detail_link_opens_detail(): void
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
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-05',
            'clock_in_at' => '2026-06-05 09:00:00',
            'clock_out_at' => '2026-06-05 18:00:00',
            'status' => 'finished',
            'note' => '通常勤務',
        ]);

        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);

        $this->actingAs($admin)
            ->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => '2026-06']))
            ->assertOk()
            ->assertSee($detailUrl, false);

        $this->actingAs($admin)
            ->get($detailUrl)
            ->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('山田太郎')
            ->assertSee('2026年')
            ->assertSee('6月 5日')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }
}
