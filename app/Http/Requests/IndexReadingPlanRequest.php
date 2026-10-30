<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class IndexReadingPlanRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 状態フィルターのバリデーションルール。
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(ReadingPlanStatus::class)],
        ];
    }

    /**
     * バリデーション失敗時は422を返す。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response('指定された状態は無効です。', 422)
        );
    }
}
