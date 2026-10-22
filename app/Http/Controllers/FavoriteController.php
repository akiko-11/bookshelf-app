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

        $from = request()->input('from');
        $page = max(1, (int) request()->input('page', 1));

        if ($from === 'favorites') {
            // お気に入り一覧から来た場合、1ページ目に戻す
            $params = [
                'book' => $book,
                'from' => 'favorites',
                'page' => 1,
            ];
        } elseif ($from === 'books') {
            // 書籍一覧から来た場合、元のページ番号を保持
            $params = [
                'book' => $book,
                'from' => 'books',
                'page' => $page,
            ];
        } elseif ($from === 'genre' && request()->filled('genre_id')) {
            // ジャンル詳細から来た場合、ジャンルIDとページ番号を保持
            $params = [
                'book' => $book,
                'from' => 'genre',
                'genre_id' => request()->input('genre_id'),
                'page' => $page,
            ];
        } else {
            // アクセス元が指定されていない場合
            $params = ['book' => $book];
        }

        return redirect()->route('books.show', $params);
    }
}
