<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookSearchRequest extends FormRequest
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
            'keyword' => 'nullable|string|max:255',
            'genre' => 'nullable|integer|exists:genres,id',
            'sort' => 'nullable|in:newest,oldest,title,rating',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください',
            'keyword.max' => 'キーワードは255文字以内で入力してください',

            'genre.integer' => 'ジャンルIDは整数で指定してください',
            'genre.exists' => '指定されたジャンルが存在しません',

            'sort.in' => '指定された並び順は無効です',

            'page.integer' => 'ページ番号は整数で指定してください',
            'page.min' => 'ページ番号は1以上で指定してください',
        ];
    }
}
