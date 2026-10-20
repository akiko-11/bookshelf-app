<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する。
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')
            ->latest()
            ->get();

        return view('genres.index', ['genres' => $genres]);
    }

    /**
     * 指定されたジャンルの詳細画面を表示する。
     *
     * @param  Genre  $genre  対象のジャンル
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->paginate(10);

        return view('genres.show', [
            'genre' => $genre,
            'books' => $books,
        ]);
    }

    /**
     * ジャンルの登録画面を表示する。
     *
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * 入力されたジャンルを登録する。
     *
     * @param  StoreGenreRequest  $request  ジャンル登録リクエスト
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Genre::create($data);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを作成しました。');
    }
}
