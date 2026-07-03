<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.integer' => 'ユーザーIDは整数で指定してください。',
            'user_id.exists' => '指定されたユーザーが見つかりません。',
            'date.date_format' => '日付は YYYY-MM-DD 形式で指定してください。',
            'month.date_format' => '月は YYYY-MM 形式で指定してください。',
            'per_page.integer' => '表示件数は整数で指定してください。',
            'per_page.min' => '表示件数は1以上で指定してください。',
            'per_page.max' => '表示件数は100以下で指定してください。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
        ];
    }
}
