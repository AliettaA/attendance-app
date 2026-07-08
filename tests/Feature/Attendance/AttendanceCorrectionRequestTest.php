<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_rejects_clock_in_after_clock_out(): void
    {
        [$user, $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.request', ['id' => $attendance->id]), [
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
        [$user, $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.request', ['id' => $attendance->id]), [
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
        [$user, $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.request', ['id' => $attendance->id]), [
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
        [$user, $attendance, $breakTime] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.request', ['id' => $attendance->id]), [
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

    public function test_rejects_missing_work_date_when_creating_by_date(): void
    {
        [$user] = $this->createAttendanceWithBreak();

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.store_by_date'), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'start' => '12:00',
                        'end' => '13:00',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'work_date' => '日付を入力してください',
        ]);
    }

    public function test_rejects_break_time_from_other_attendance(): void
    {
        [$user, $attendance] = $this->createAttendanceWithBreak();

        $otherAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-06',
            'clock_in_at' => '2026-06-06 09:00:00',
            'clock_out_at' => '2026-06-06 18:00:00',
            'status' => 'finished',
            'note' => '通常勤務',
        ]);
        $otherBreakTime = BreakTime::create([
            'attendance_id' => $otherAttendance->id,
            'break_start_at' => '2026-06-06 12:00:00',
            'break_end_at' => '2026-06-06 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.request', ['id' => $attendance->id]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $otherBreakTime->id,
                        'start' => '12:00',
                        'end' => '13:00',
                    ],
                ],
                'note' => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_request_is_created_and_shown_to_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        [$user, $attendance, $breakTime] = $this->createAttendanceWithBreak();
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.request', ['id' => $attendance->id]), [
                'clock_in_at' => '10:00',
                'clock_out_at' => '19:00',
                'breaks' => [
                    [
                        'original_break_time_id' => $breakTime->id,
                        'start' => '13:00',
                        'end' => '14:00',
                    ],
                ],
                'note' => '電車遅延のため',
            ]);

        $response->assertRedirect(route('attendance.detail.show', ['id' => $attendance->id]));

        $this->assertDatabaseHas('correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-06-05 10:00:00',
            'requested_clock_out_at' => '2026-06-05 19:00:00',
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $correctionRequest = CorrectionRequest::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('correction_request_breaks', [
            'correction_request_id' => $correctionRequest->id,
            'original_break_time_id' => $breakTime->id,
            'requested_break_start_at' => '2026-06-05 13:00:00',
            'requested_break_end_at' => '2026-06-05 14:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('correction_requests.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('山田太郎')
            ->assertSee('電車遅延のため');

        $this->actingAs($admin)
            ->get(route('admin.correction_requests.show', ['attendance_correct_request_id' => $correctionRequest->id]))
            ->assertOk()
            ->assertSee('修正申請承認')
            ->assertSee('山田太郎')
            ->assertSee('電車遅延のため')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    public function test_pending_requests_are_shown(): void
    {
        [$user, $attendance] = $this->createAttendanceWithBreak();

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-06-05 10:00:00',
            'requested_clock_out_at' => '2026-06-05 19:00:00',
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('correction_requests.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('承認待ち')
            ->assertSee('山田太郎')
            ->assertSee('2026/06/05')
            ->assertSee('電車遅延のため');
    }

    public function test_approved_requests_are_shown(): void
    {
        [$user, $attendance, $breakTime] = $this->createAttendanceWithBreak();
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-06-05 10:00:00',
            'requested_clock_out_at' => '2026-06-05 19:00:00',
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        CorrectionRequestBreak::create([
            'correction_request_id' => $correctionRequest->id,
            'original_break_time_id' => $breakTime->id,
            'requested_break_start_at' => '2026-06-05 13:00:00',
            'requested_break_end_at' => '2026-06-05 14:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.correction_requests.approve', ['attendance_correct_request_id' => $correctionRequest->id]))
            ->assertRedirect(route('correction_requests.index', ['status' => 'pending']));

        $this->actingAs($user)
            ->get(route('correction_requests.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee('承認済み')
            ->assertSee('山田太郎')
            ->assertSee('2026/06/05')
            ->assertSee('電車遅延のため');
    }

    public function test_detail_link_opens_detail(): void
    {
        [$user, $attendance] = $this->createAttendanceWithBreak();

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-06-05 10:00:00',
            'requested_clock_out_at' => '2026-06-05 19:00:00',
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $detailUrl = route('attendance.detail.show', ['id' => $attendance->id]);

        $this->actingAs($user)
            ->get(route('correction_requests.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($detailUrl, false);

        $this->actingAs($user)
            ->get($detailUrl)
            ->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('山田太郎')
            ->assertSee('2026年')
            ->assertSee('6月 5日');
    }

    private function createAttendanceWithBreak(): array
    {
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

        return [$user, $attendance, $breakTime];
    }
}
