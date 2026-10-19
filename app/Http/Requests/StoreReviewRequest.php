<?php

namespace App\Http\Requests;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
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
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください',
            'rating.integer' => '評価は1から5の整数を選択してください',
            'rating.between' => '評価は1から5の範囲で選択してください',
            'comment.required' => 'コメントを入力してください',
            'comment.string' => 'コメントは文字列で入力してください',
            'comment.max' => 'コメントは1000文字以内で入力してください',
        ];
    }

    /**
     * 同一ユーザーによる同一書籍へのレビュー重複投稿を検証する。
     *
     * @param  Validator  $validator  バリデーター
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $book = $this->route('book');

            // ログインユーザーがいない、またはBookモデルを取得できなかった場合
            if (! $user || ! ($book instanceof Book)) {
                return;
            }

            // 同じユーザーが同じ書籍にレビュー投稿済み確認
            $hasReviewed = Review::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->exists();

            if ($hasReviewed) {
                $validator->errors()->add(
                    'review',
                    'この書籍には既にレビューを投稿しています。'
                );
            }
        });
    }
}
