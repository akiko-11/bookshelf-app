<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateReadingPlanRequest extends FormRequest
{
    /**
     * 読書計画の更新権限を確認する。
     */
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('plan'));

        return true;
    }

    /**
     * 読書計画更新時のバリデーションルールを取得する。
     */
    public function rules(): array
    {
        return [
            'target_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.today('Asia/Tokyo')->toDateString(),
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージ。
     */
    public function messages(): array
    {
        return [
            'target_date.required' => '期日を入力してください',
            'target_date.date_format' => '期日は有効な日付形式で入力してください',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください',
        ];
    }
}
