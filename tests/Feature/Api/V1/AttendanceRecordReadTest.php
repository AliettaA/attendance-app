<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_records_can_be_fetched_as_json(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => 'テスト勤怠',
        ]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }
    public function test_attendance_record_detail_can_be_fetched_as_json(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => '詳細取得テスト',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-24 12:00:00',
            'break_end_at' => '2026-06-24 13:00:00',
        ]);

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-06-24 09:30:00',
            'requested_clock_out_at' => '2026-06-24 18:30:00',
            'requested_note' => '修正申請テスト',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $attendance->id);
        $response->assertJsonPath('data.user.name', '山田太郎');
        $response->assertJsonPath('data.breaks.0.break_start', '12:00:00');
        $response->assertJsonPath('data.correction_requests.0.status', 'pending');
    }

    public function test_not_found_error_is_returned_when_attendance_record_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}