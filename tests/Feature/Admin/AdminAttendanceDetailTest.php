<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_shows_attendance(): void
    {
        [$admin, $user, $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.show', ['id' => $attendance->id]));

        $response->assertOk();
        $response->assertSee('勤怠詳細');
        $response->assertSee($user->name);
        $response->assertSee('2026年');
        $response->assertSee('6月 5日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
        $response->assertSee('name="breaks[0][original_break_time_id]"', false);
        $response->assertSee('value="'.$breakTime->id.'"', false);
    }

    public function test_rejects_clock_in_after_clock_out(): void
    {
        [$admin, , $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in_at' => '19:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $breakTime->id,
                        'start' => '12:00',
                        'end' => '13:00',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'clock_out_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_rejects_break_start_after_clock_out(): void
    {
        [$admin, , $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $breakTime->id,
                        'start' => '19:00',
                        'end' => '20:00',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_rejects_break_end_after_clock_out(): void
    {
        [$admin, , $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $breakTime->id,
                        'start' => '17:00',
                        'end' => '19:00',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_rejects_empty_note(): void
    {
        [$admin, , $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $breakTime->id,
                        'start' => '12:00',
                        'end' => '13:00',
                    ],
                ],
                'note' => '',
            ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    public function test_attendance_is_updated(): void
    {
        [$admin, , $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in_at' => '10:00',
                'clock_out_at' => '19:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $breakTime->id,
                        'start' => '13:00',
                        'end' => '14:00',
                    ],
                ],
                'note' => '管理者修正',
            ]);

        $response->assertRedirect(route('admin.attendance.show', ['id' => $attendance->id]));
        $response->assertSessionHas('status', '勤怠情報を更新しました。');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in_at' => '2026-06-05 10:00:00',
            'clock_out_at' => '2026-06-05 19:00:00',
            'note' => '管理者修正',
        ]);

        $this->assertDatabaseHas('break_times', [
            'id' => $breakTime->id,
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-05 13:00:00',
            'break_end_at' => '2026-06-05 14:00:00',
        ]);
    }

    private function createAttendanceWithBreak(): array
    {
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
        $breakTime = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-05 12:00:00',
            'break_end_at' => '2026-06-05 13:00:00',
        ]);

        return [$admin, $user, $attendance, $breakTime];
    }
}
