<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'name')
                    ->ignore($this->route('genre')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名は必須です',
            'name.string' => 'ジャンル名は文字列で入力してください',
            'name.max' => 'ジャンル名は255文字以内で入力してください',
            'name.unique' => 'そのジャンル名は既に使用されています',
        ];
    }
}
