<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_record_can_be_created(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'API作成テスト',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.date', '2026-06-24');
        $response->assertJsonPath('data.clock_in', '09:00:00');
        $response->assertJsonPath('data.clock_out', '18:00:00');
        $response->assertJsonPath('data.comment', 'API作成テスト');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'note' => 'API作成テスト',
        ]);
    }

    public function test_validation_error_is_returned_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'date',
            'clock_in',
        ]);
        $response->assertJsonPath('errors.date.0', '勤怠日は必須です。');
        $response->assertJsonPath('errors.clock_in.0', '出勤時刻は必須です。');
    }

    public function test_attendance_record_can_be_updated(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => '更新前',
        ]);

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-06-24',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '更新後',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.clock_in', '10:00:00');
        $response->assertJsonPath('data.clock_out', '19:00:00');
        $response->assertJsonPath('data.comment', '更新後');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_date' => '2026-06-24',
            'note' => '更新後',
        ]);
    }

    public function test_not_found_error_is_returned_when_updating_missing_attendance_record(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/attendance-records/99999', [
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '存在しない勤怠',
        ]);

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
    public function test_attendance_record_can_be_deleted(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => '削除テスト',
        ]);

        $response = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }
    public function test_not_found_error_is_returned_when_deleting_missing_attendance_record(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}