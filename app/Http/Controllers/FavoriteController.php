<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FavoriteController extends Controller
{
    /**
     * ログインユーザーのお気に入り書籍一覧を表示する。
     */
    public function index(): View
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);

        return view('favorites.index', ['books' => $books]);
    }

    /**
     * 指定された書籍のお気に入りを追加・解除する。
     */
    public function toggle(Book $book): RedirectResponse
    {
        $user = auth()->user();

        if ($user->favoriteBooks()->where('book_id', $book->id)->exists()) {
            // すでにお気に入り済みの場合、解除
            $user->favoriteBooks()->detach($book->id);
        } else {
            $user->favoriteBooks()->attach($book->id);
        }

        return redirect()->route('books.show', $book);
    }
}
