<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    /**
     * レビューのいいねを追加または解除し、書籍詳細画面へリダイレクトする。
     *
     * @param  Review  $review  いいね対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     */
    public function toggle(Review $review): RedirectResponse
    {
        $this->authorize('like', $review);

        $user = auth()->user();

        if ($user->likedReviews()->where('review_id', $review->id)->exists()) {
            // すでにいいね済みの場合、解除
            $user->likedReviews()->detach($review->id);

            $message = 'いいねを解除しました。';
        } else {
            // いいねされていない場合、追加
            $user->likedReviews()->attach($review->id);

            $message = 'いいねしました。';
        }

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', $message);
    }
}
