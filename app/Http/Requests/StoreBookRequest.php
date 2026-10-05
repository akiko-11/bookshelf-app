<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|digits:13|unique:books,isbn',
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
            'title.required' => 'タイトルを入力してください',
            'title.string' => 'タイトルは文字列で入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',

            'author.required' => '著者名を入力してください',
            'author.string' => '著者名は文字列で入力してください',
            'author.max' => '著者名は255文字以内で入力してください',

            'isbn.required' => 'ISBNを入力してください',
            'isbn.digits' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'そのISBNは既に使用されています',

            'published_date.required' => '出版日を入力してください',
            'published_date.date' => '出版日は有効な日付形式で入力してください',

            'description.string' => '説明は文字列で入力してください',

            'image_url.url' => '画像URLは有効なURL形式で入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',

            'genres.required' => 'ジャンルは1つ以上選択してください',
            'genres.array' => 'ジャンルの形式が正しくありません',
            'genres.min' => 'ジャンルは1つ以上選択してください',
            'genres.*.integer' => 'ジャンルIDは整数で入力してください',
            'genres.*.distinct' => '同じジャンルを重複して選択することはできません',
            'genres.*.exists' => '選択されたジャンルが存在しません',
        ];
    }
}
