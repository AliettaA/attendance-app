<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'work_date' => [$this->requiresWorkDate() ? 'required' : 'nullable', 'date_format:Y-m-d'],
            'clock_in_at' => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.original_break_time_id' => ['nullable', 'integer', 'exists:break_times,id'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $clockIn = $this->input('clock_in_at');
            $clockOut = $this->input('clock_out_at');
            $attendance = $this->routeAttendance();

            if ($clockIn && $clockOut && $clockOut <= $clockIn) {
                $validator->errors()->add(
                    'clock_out_at',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            foreach ($this->input('breaks', []) as $index => $break) {
                $breakStart = $break['start'] ?? null;
                $breakEnd = $break['end'] ?? null;
                $originalBreakTimeId = $break['original_break_time_id'] ?? null;

                if ($originalBreakTimeId && ! $this->breakTimeBelongsToAttendance($attendance, (int) $originalBreakTimeId)) {
                    $validator->errors()->add(
                        "breaks.{$index}.start",
                        '休憩時間が不適切な値です'
                    );

                    continue;
                }

                if (! $breakStart && ! $breakEnd) {
                    continue;
                }

                if ($breakStart && ! $breakEnd) {
                    $validator->errors()->add(
                        "breaks.{$index}.end",
                        '休憩戻り時間を入力してください'
                    );

                    continue;
                }

                if (! $breakStart && $breakEnd) {
                    $validator->errors()->add(
                        "breaks.{$index}.start",
                        '休憩入り時間を入力してください'
                    );

                    continue;
                }

                if ($clockIn && $clockOut && ($breakStart < $clockIn || $breakStart > $clockOut)) {
                    $validator->errors()->add(
                        "breaks.{$index}.start",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($clockOut && $breakEnd > $clockOut) {
                    $validator->errors()->add(
                        "breaks.{$index}.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                if ($breakEnd <= $breakStart) {
                    $validator->errors()->add(
                        "breaks.{$index}.end",
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'clock_in_at.required' => '出勤時間を入力してください',
            'clock_in_at.date_format' => '出勤時間を入力してください',
            'clock_out_at.required' => '退勤時間を入力してください',
            'clock_out_at.date_format' => '退勤時間を入力してください',
            'work_date.required' => '日付を入力してください',
            'work_date.date_format' => '日付を入力してください',
            'breaks.*.start.date_format' => '休憩入り時間を入力してください',
            'breaks.*.end.date_format' => '休憩戻り時間を入力してください',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    private function requiresWorkDate(): bool
    {
        return $this->routeIs(
            'attendance.detail.store_by_date',
            'admin.attendance.staff.detail.store'
        );
    }

    private function routeAttendance(): ?Attendance
    {
        if (! $this->routeIs('attendance.detail.request', 'admin.attendance.update')) {
            return null;
        }

        return Attendance::find($this->route('id'));
    }

    private function breakTimeBelongsToAttendance(?Attendance $attendance, int $breakTimeId): bool
    {
        if (! $attendance) {
            return false;
        }

        return $attendance->breakTimes()
            ->whereKey($breakTimeId)
            ->exists();
    }
}
