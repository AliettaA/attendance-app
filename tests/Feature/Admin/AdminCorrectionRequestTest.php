<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_see_all_pending_correction_requests(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user1 = User::factory()->create(['name' => '山田太郎', 'role' => 'user', 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['name' => '佐藤花子', 'role' => 'user', 'email_verified_at' => now()]);

        $this->createCorrectionRequest($user1, 'pending', '電車遅延のため');
        $this->createCorrectionRequest($user2, 'pending', '休憩時間修正のため');
        $this->createCorrectionRequest($user1, 'approved', '承認済みの申請', '2026-06-06');

        $this->actingAs($admin)
            ->get(route('correction_requests.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('承認待ち')
            ->assertSee('山田太郎')
            ->assertSee('電車遅延のため')
            ->assertSee('佐藤花子')
            ->assertSee('休憩時間修正のため')
            ->assertDontSee('承認済みの申請');
    }

    public function test_admin_can_see_all_approved_correction_requests(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user1 = User::factory()->create(['name' => '山田太郎', 'role' => 'user', 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['name' => '佐藤花子', 'role' => 'user', 'email_verified_at' => now()]);

        $this->createCorrectionRequest($user1, 'approved', '承認済み申請1');
        $this->createCorrectionRequest($user2, 'approved', '承認済み申請2');
        $this->createCorrectionRequest($user1, 'pending', '承認待ちの申請', '2026-06-06');

        $this->actingAs($admin)
            ->get(route('correction_requests.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee('承認済み')
            ->assertSee('山田太郎')
            ->assertSee('承認済み申請1')
            ->assertSee('佐藤花子')
            ->assertSee('承認済み申請2')
            ->assertDontSee('承認待ちの申請');
    }

    public function test_admin_can_see_correction_request_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['name' => '山田太郎', 'role' => 'user', 'email_verified_at' => now()]);
        $correctionRequest = $this->createCorrectionRequest($user, 'pending', '電車遅延のため');

        $this->actingAs($admin)
            ->get(route('admin.correction_requests.show', ['attendance_correct_request_id' => $correctionRequest->id]))
            ->assertOk()
            ->assertSee('修正申請承認')
            ->assertSee('山田太郎')
            ->assertSee('2026年')
            ->assertSee('6月 5日')
            ->assertSee('10:00')
            ->assertSee('19:00')
            ->assertSee('13:00')
            ->assertSee('14:00')
            ->assertSee('電車遅延のため');
    }

    public function test_admin_can_approve_correction_request_and_attendance_is_updated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['name' => '山田太郎', 'role' => 'user', 'email_verified_at' => now()]);
        $correctionRequest = $this->createCorrectionRequest($user, 'pending', '電車遅延のため');
        $attendance = $correctionRequest->attendance;
        $originalBreakTimeId = $correctionRequest->correctionRequestBreaks->first()->original_break_time_id;

        $this->actingAs($admin)
            ->post(route('admin.correction_requests.approve', ['attendance_correct_request_id' => $correctionRequest->id]))
            ->assertRedirect(route('correction_requests.index', ['status' => 'pending']));

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => '2026-06-19 10:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in_at' => '2026-06-05 10:00:00',
            'clock_out_at' => '2026-06-05 19:00:00',
            'note' => '電車遅延のため',
        ]);

        $this->assertDatabaseHas('break_times', [
            'id' => $originalBreakTimeId,
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-06-05 13:00:00',
            'break_end_at' => '2026-06-05 14:00:00',
        ]);
    }

    private function createCorrectionRequest(User $user, string $status, string $note, string $workDate = '2026-06-05'): CorrectionRequest
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'clock_in_at' => $workDate.' 09:00:00',
            'clock_out_at' => $workDate.' 18:00:00',
            'status' => 'finished',
            'note' => '通常勤務',
        ]);

        $breakTime = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $workDate.' 12:00:00',
            'break_end_at' => $workDate.' 13:00:00',
        ]);

        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => $workDate.' 10:00:00',
            'requested_clock_out_at' => $workDate.' 19:00:00',
            'requested_note' => $note,
            'status' => $status,
        ]);

        CorrectionRequestBreak::create([
            'correction_request_id' => $correctionRequest->id,
            'original_break_time_id' => $breakTime->id,
            'requested_break_start_at' => $workDate.' 13:00:00',
            'requested_break_end_at' => $workDate.' 14:00:00',
        ]);

        return $correctionRequest->load(['attendance', 'correctionRequestBreaks']);
    }
}
