<?php

namespace App\Http\Requests;

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

            if ($clockIn && $clockOut && $clockOut <= $clockIn) {
                $validator->errors()->add(
                    'clock_out_at',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            foreach ($this->input('breaks', []) as $break) {
                $breakStart = $break['start'] ?? null;
                $breakEnd = $break['end'] ?? null;

                if (! $breakStart && ! $breakEnd) {
                    continue;
                }

                if ($breakStart && ! $breakEnd) {
                    $validator->errors()->add(
                        'breaks',
                        '休憩戻り時間を入力してください'
                    );
                    continue;
                }

                if (! $breakStart && $breakEnd) {
                    $validator->errors()->add(
                        'breaks',
                        '休憩入り時間を入力してください'
                    );
                    continue;
                }

                if ($clockIn && $clockOut && ($breakStart < $clockIn || $breakStart > $clockOut)) {
                    $validator->errors()->add(
                        'breaks',
                        '休憩時間が不適切な値です'
                    );
                }

                if ($clockOut && $breakEnd > $clockOut) {
                    $validator->errors()->add(
                        'breaks',
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                if ($breakEnd <= $breakStart) {
                    $validator->errors()->add(
                        'breaks',
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
            'breaks.*.start.date_format' => '休憩入り時間を入力してください',
            'breaks.*.end.date_format' => '休憩戻り時間を入力してください',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }
}