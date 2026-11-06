<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Services\BookService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class BookController extends Controller
{
    /**
     * 書籍一覧画面を表示する。
     *
     * @param  BookSearchRequest  $request  バリデーション済みの書籍検索リクエスト
     * @return View 書籍一覧画面
     */
    public function index(BookSearchRequest $request): View
    {
        $keyword = $request->validated('keyword');
        $genre = $request->validated('genre');
        $sort = $request->validated('sort') ?: 'newest';

        $direction = $sort === 'oldest' ? 'asc' : 'desc';

        // キーワード検索
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->when($keyword !== null && $keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($genre !== null && $genre !== '', function ($query) use ($genre) {
                $query->whereHas('genres', function ($subQuery) use ($genre) {
                    $subQuery->where('genres.id', $genre);
                });
            })
            ->when(in_array($sort, ['newest', 'oldest'], true), function ($query) use ($direction) {
                $query->orderBy('created_at', $direction)
                    ->orderBy('books.id');
            })
            ->when($sort === 'title', function ($query) {
                $query->orderBy('title', 'asc')
                    ->orderBy('books.id');
            })
            ->when($sort === 'rating', function ($query) {
                $query->orderByDesc('reviews_avg_rating')
                    ->orderBy('books.id');
            })
            ->paginate(10);

        // ジャンル一覧を取得
        $genres = Genre::all();

        return view('books.index', [
            'books' => $books,
            'genres' => $genres,
        ]);
    }

    /**
     * 指定された書籍の詳細画面を表示する。
     *
     * @param  Book  $book  表示対象の書籍
     * @return View 書籍詳細画面
     */
    public function show(Book $book): View
    {
        $user = auth()->user();

        $hasReviewed = false;

        // ログインユーザーがこの書籍にレビュー投稿済みか確認
        if ($user) {
            $hasReviewed = $user->reviews()
                ->where('book_id', $book->id)
                ->exists();
        }

        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', [
            'book' => $book,
            'hasReviewed' => $hasReviewed,
        ]);
    }

    /**
     * 書籍登録画面を表示する。
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', ['genres' => $genres]);
    }

    /**
     * 書籍を登録し、選択されたジャンルを紐付ける。
     *
     * @param  StoreBookRequest  $request  書籍登録リクエスト
     * @param  BookService  $bookService  書籍操作サービス
     * @return RedirectResponse 登録結果のリダイレクト
     */
    public function store(
        StoreBookRequest $request,
        BookService $bookService
    ): RedirectResponse {
        try {
            // Serviceで書籍登録とジャンル紐付けを実行
            $book = $bookService->createBook(
                $request->validated(),
                $request->user()->id
            );
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('books.create')
                ->withInput()
                ->with('error', '書籍の登録に失敗しました。');
        }

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍編集画面を表示する。
     *
     * @param  Book  $book  更新対象の書籍
     * @return View 書籍編集画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報とジャンルの紐付けを更新する。
     *
     * @param  Book  $book  更新対象の書籍
     * @param  UpdateBookRequest  $request  書籍更新リクエスト
     * @param  BookService  $bookService  書籍操作サービス
     * @return RedirectResponse 更新結果のリダイレクト
     */
    public function update(
        Book $book,
        UpdateBookRequest $request,
        BookService $bookService
    ): RedirectResponse {
        // 書籍の所有者認可
        $this->authorize('update', $book);

        try {
            // Serviceで書籍とジャンルを更新
            $bookService->updateBook(
                $book,
                $request->validated()
            );

            return redirect()
                ->route('books.show', $book)
                ->with('success', '書籍を更新しました。');

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', '書籍の更新に失敗しました。');
        }
    }

    /**
     * 書籍と関連する読書計画・通知を削除する。
     *
     * @param  Book  $book  削除対象の書籍
     * @param  BookService  $bookService  書籍操作サービス
     * @return RedirectResponse 削除結果のリダイレクトレスポンス
     */
    public function destroy(
        Book $book,
        BookService $bookService
    ): RedirectResponse {
        // 書籍の所有者認可
        $this->authorize('delete', $book);

        try {
            // Serviceで書籍と関連データを削除
            $bookService->deleteBook($book);

        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('books.show', $book)
                ->with('error', '書籍の削除に失敗しました。');
        }

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
