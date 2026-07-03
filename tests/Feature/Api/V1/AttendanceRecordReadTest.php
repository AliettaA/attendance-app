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

    public function test_records_are_fetched(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => 'テスト勤怠',
        ])->breakTimes()->create([
            'break_start_at' => '2026-06-24 12:00:00',
            'break_end_at' => '2026-06-24 13:00:00',
        ]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk();
        $response->assertJsonPath('data.0.total_break_time', '01:00');
        $response->assertJsonPath('data.0.total_time', '08:00');
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

    public function test_detail_is_fetched(): void
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
        $response->assertJsonPath('data.breaks.0.break_in', '12:00:00');
        $response->assertJsonPath('data.applications.0.status', 'pending');
    }

    public function test_missing_record_returns_not_found(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}
