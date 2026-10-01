<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BookIndexRequest extends FormRequest
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
            'keyword' => 'nullable|string',
            'genre_id' => 'nullable|integer|exists:genres,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください',
            'genre_id.integer' => 'ジャンルIDは整数で入力してください',
            'genre_id.exists' => '指定されたジャンルが存在しません',
            'page.integer' => 'ページ番号は整数で入力してください',
            'page.min' => 'ページ番号は1以上で指定してください',
            'per_page.integer' => '1ページあたりの件数は整数で入力してください',
            'per_page.min' => '1ページあたりの件数は1件以上で指定してください',
            'per_page.max' => '1ページあたりの件数は100件以内で指定してください',
        ];
    }
}
