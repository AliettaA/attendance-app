<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = $this->createUser('一般ユーザー1', 'user1@example.com', 'user');
        $user2 = $this->createUser('一般ユーザー2', 'user2@example.com', 'user');
        $admin = $this->createUser('管理者ユーザー', 'user3@example.com', 'admin');

        $this->seedIntentionalUser1Data($user1);
        $this->seedRegularMonthlyData($user2, Carbon::today()->startOfMonth()->copy()->subMonths(4), 12);
        $this->seedRegularMonthlyData($admin, Carbon::today()->startOfMonth()->copy()->subMonths(2), 8);
    }

    private function createUser(string $name, string $email, string $role): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function seedIntentionalUser1Data(User $user): void
    {
        $currentMonth = Carbon::today()->startOfMonth();

        for ($i = 5; $i >= 1; $i--) {
            $month = $currentMonth->copy()->subMonths($i);

            foreach ($this->weekdayDates($month, 15) as $date) {
                $this->createAttendanceWithBreak($user, $date, '09:00', '18:00');
            }
        }

        $patterns = array_merge(
            array_fill(0, 10, ['09:00', '18:00', null]),
            array_fill(0, 3, ['09:00', '20:00', '残業']),
            array_fill(0, 2, ['09:30', '18:00', '遅刻']),
            [['09:00', '17:00', '早退']],
            [['08:00', '21:00', '長時間労働']]
        );

        foreach ($this->weekdayDates($currentMonth, 17) as $index => $date) {
            [$clockIn, $clockOut, $note] = $patterns[$index];
            $this->createAttendanceWithBreak($user, $date, $clockIn, $clockOut, $note);
        }
    }

    private function seedRegularMonthlyData(User $user, Carbon $startMonth, int $daysPerMonth): void
    {
        $currentMonth = Carbon::today()->startOfMonth();

        for ($month = $startMonth->copy(); $month->lte($currentMonth); $month->addMonth()) {
            foreach ($this->weekdayDates($month, $daysPerMonth) as $index => $date) {
                $pattern = $index % 6;

                [$clockIn, $clockOut, $note] = match ($pattern) {
                    0 => ['08:50', '18:10', null],
                    1 => ['09:00', '18:00', null],
                    2 => ['09:15', '18:30', '遅刻'],
                    3 => ['09:00', '19:00', '残業'],
                    4 => ['08:45', '17:45', '早退'],
                    default => ['09:00', '18:15', null],
                };

                $this->createAttendanceWithBreak($user, $date, $clockIn, $clockOut, $note);
            }
        }
    }

    private function weekdayDates(Carbon $month, int $limit): array
    {
        $dates = [];

        for ($date = $month->copy()->startOfMonth(); $date->isSameMonth($month); $date->addDay()) {
            if ($date->isWeekday()) {
                $dates[] = $date->copy();
            }

            if (count($dates) === $limit) {
                break;
            }
        }

        return $dates;
    }

    private function createAttendanceWithBreak(
        User $user,
        Carbon $date,
        string $clockIn,
        string $clockOut,
        ?string $note = null
    ): void {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => Carbon::parse($date->toDateString() . ' ' . $clockIn),
            'clock_out_at' => Carbon::parse($date->toDateString() . ' ' . $clockOut),
            'status' => 'finished',
            'note' => $note,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::parse($date->toDateString() . ' 12:00'),
            'break_end_at' => Carbon::parse($date->toDateString() . ' 13:00'),
        ]);
    }
}
