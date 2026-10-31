<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingPlanRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 読書計画登録時のバリデーションルール。
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'bail',
                'required',
                'integer',
                'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where(function ($query) {
                        return $query
                            ->where('user_id', $this->user()->id)
                            ->where('status', ReadingPlanStatus::InProgress->value);
                    }),
            ],
            'target_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージ。
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください',
            'book_id.integer' => '書籍IDは整数で入力してください',
            'book_id.exists' => '選択された書籍が存在しません',
            'book_id.unique' => 'この書籍の読書計画は既に登録されています',

            'target_date.required' => '期日を入力してください',
            'target_date.date_format' => '有効な日付を入力してください',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください',
        ];
    }
}
