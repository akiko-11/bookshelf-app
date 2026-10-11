<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /**
     * レビューを登録し、書籍詳細画面へリダイレクトする。
     *
     * @param  Book  $book  レビュー対象の書籍
     * @param  StoreReviewRequest  $request  レビュー投稿リクエスト
     * @return RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     */
    public function store(Book $book, StoreReviewRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = auth()->user();

        $data['user_id'] = $user->id;
        $data['book_id'] = $book->id;

        Review::create($data);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビュー編集画面を表示する。
     *
     * @param  Review  $review  更新対象のレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新し、書籍詳細画面へリダイレクトする。
     *
     * @param  Review  $review  更新対象のレビュー
     * @param  UpdateReviewRequest  $request  レビュー更新リクエスト
     * @return RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     */
    public function update(Review $review, UpdateReviewRequest $request): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除し、書籍詳細画面へリダイレクトする。
     *
     * @param  Review  $review  削除対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }
}
