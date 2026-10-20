<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Contracts\View\View;

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
}
