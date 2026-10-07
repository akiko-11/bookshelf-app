<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Book;
use App\Models\Review;
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
}
