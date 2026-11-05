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

        $params = ['book' => $review->book];

        $from = request()->input('from');

        if ($from === 'reports') {
            // マイレポートから来た場合
            $params['from'] = 'reports';

        } elseif ($from === 'ranking') {
            // 評価ランキングから来た場合、遷移元を保持
            $params['from'] = 'ranking';

        } elseif ($from === 'reading-plans') {
            // 読書計画一覧から来た場合
            $params['from'] = 'reading-plans';

            $status = request()->input('status');

            if (is_string($status) && $status !== '') {
                $params['status'] = $status;
            }

        } elseif ($from === 'favorites') {
            // お気に入り一覧から来た場合
            $params['from'] = 'favorites';
            $params['page'] = max(1, (int) request()->input('page', 1));

        } elseif ($from === 'genre' && request()->filled('genre_id')) {
            // ジャンル詳細から来た場合
            $params['from'] = 'genre';
            $params['genre_id'] = request()->input('genre_id');
            $params['page'] = max(1, (int) request()->input('page', 1));
        }

        return redirect()
            ->route('books.show', $params)
            ->with('success', $message);
    }
}
