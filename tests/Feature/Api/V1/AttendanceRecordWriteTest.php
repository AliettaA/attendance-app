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

    public function test_record_is_created(): void
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

    public function test_rejects_missing_required_fields(): void
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

    public function test_rejects_invalid_create_values(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026/06/24',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.date.0', '勤怠日は YYYY-MM-DD 形式で指定してください。');
        $response->assertJsonPath('errors.clock_in.0', '出勤時刻は HH:MM:SS 形式で指定してください。');
        $response->assertJsonPath('errors.clock_out.0', '退勤時刻は HH:MM:SS 形式で指定してください。');
        $response->assertJsonPath('errors.comment.0', '備考は 255 文字以内で入力してください。');
    }

    public function test_rejects_duplicate_create_date(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
        ]);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-06-24',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.date.0', 'この日付の勤怠は既に登録されています。');
    }

    public function test_rejects_create_clock_out_before_clock_in(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '08:59:59',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.clock_out.0', '退勤時刻は出勤時刻より後の時刻を指定してください。');
    }

    public function test_record_is_updated(): void
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

    public function test_rejects_invalid_update_values(): void
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
        ]);

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026/06/24',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.date.0', '勤怠日は YYYY-MM-DD 形式で指定してください。');
        $response->assertJsonPath('errors.clock_in.0', '出勤時刻は HH:MM:SS 形式で指定してください。');
        $response->assertJsonPath('errors.clock_out.0', '退勤時刻は HH:MM:SS 形式で指定してください。');
        $response->assertJsonPath('errors.comment.0', '備考は 255 文字以内で入力してください。');
    }

    public function test_rejects_duplicate_update_date(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-25',
            'clock_in_at' => '2026-06-25 09:00:00',
            'clock_out_at' => '2026-06-25 18:00:00',
            'status' => 'finished',
        ]);

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-06-24',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.date.0', 'この日付の勤怠は既に登録されています。');
    }

    public function test_rejects_update_clock_out_before_clock_in(): void
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
        ]);

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '08:59:59',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.clock_out.0', '退勤時刻は出勤時刻より後の時刻を指定してください。');
    }

    public function test_update_missing_record_returns_not_found(): void
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

    public function test_record_is_deleted(): void
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

    public function test_delete_missing_record_returns_not_found(): void
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
