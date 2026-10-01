<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => [
                'required',
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'published_date' => 'required|date',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'genres' => 'required|array|min:1',
            'genres.*' => 'integer|distinct|exists:genres,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者IDは必須です',
            'user_id.integer' => '登録者IDは整数で入力してください',
            'user_id.exists' => '指定されたユーザーが存在しません',

            'title.required' => 'タイトルは必須です',
            'title.string' => 'タイトルは文字列で入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',

            'author.required' => '著者名は必須です',
            'author.string' => '著者名は文字列で入力してください',
            'author.max' => '著者名は255文字以内で入力してください',

            'isbn.required' => 'ISBNは必須です',
            'isbn.digits' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'そのISBNは既に使用されています',

            'published_date.required' => '出版日は必須です',
            'published_date.date' => '出版日は有効な日付形式で入力してください',

            'description.string' => '説明は文字列で入力してください',

            'image_url.url' => '画像URLは有効なURL形式で入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',

            'genres.required' => 'ジャンルを1つ以上選択してください',
            'genres.array' => 'ジャンルの形式が正しくありません',
            'genres.min' => 'ジャンルを1つ以上選択してください',
            'genres.*.integer' => 'ジャンルIDは整数で入力してください',
            'genres.*.distinct' => '同じジャンルを重複して選択することはできません',
            'genres.*.exists' => '選択されたジャンルが存在しません',
        ];
    }
}
