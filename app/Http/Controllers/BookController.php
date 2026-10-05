<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧画面を表示する。
     *
     * @return View 書籍一覧画面
     */
    public function index(): View
    {
        $books = Book::with('genres')
            ->latest()
            ->paginate(10);

        return view('books.index', ['books' => $books]);
    }

    /**
     * 指定された書籍の詳細画面を表示する。
     *
     * @param  Book  $book  表示対象の書籍
     * @return View 書籍詳細画面
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', ['book' => $book]);
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
     * @param  StoreBookRequest  $request  バリデーション済みの書籍登録リクエスト
     * @return RedirectResponse 登録した書籍の詳細画面へのリダイレクト
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $genreIds = $data['genres'];
        unset($data['genres']);

        $data['user_id'] = $request->user()->id;

        $book = DB::transaction(function () use ($data, $genreIds) {
            $book = Book::create($data);

            $book->genres()->sync($genreIds);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }
}
