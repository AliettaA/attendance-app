<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_write(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => '未認証テスト',
        ]);

        $postResponse = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '未認証POST',
        ]);

        $putResponse = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-06-24',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '未認証PUT',
        ]);

        $deleteResponse = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $postResponse->assertUnauthorized();
        $putResponse->assertUnauthorized();
        $deleteResponse->assertUnauthorized();

        $postResponse->assertExactJson(['message' => 'Unauthenticated.']);
        $putResponse->assertExactJson(['message' => 'Unauthenticated.']);
        $deleteResponse->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_user_can_update_and_delete_own_record(): void
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
            'note' => '本人操作テスト',
        ]);

        $updateResponse = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-06-24',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '本人更新',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.comment', '本人更新');

        $deleteResponse = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $deleteResponse->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    public function test_user_cannot_change_other_user_record(): void
    {
        $owner = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($otherUser);

        $attendance = Attendance::create([
            'user_id' => $owner->id,
            'work_date' => '2026-06-24',
            'clock_in_at' => '2026-06-24 09:00:00',
            'clock_out_at' => '2026-06-24 18:00:00',
            'status' => 'finished',
            'note' => '他ユーザー操作テスト',
        ]);

        $updateResponse = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-06-24',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '他ユーザー更新',
        ]);

        $deleteResponse = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $updateResponse->assertForbidden();
        $deleteResponse->assertForbidden();

        $updateResponse->assertExactJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);

        $deleteResponse->assertExactJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'note' => '他ユーザー操作テスト',
        ]);
    }
}
