<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;

class RankController extends Controller
{
    /**
     * レビュー平均評価の高い書籍を上位10件表示する。
     *
     * @return View ランキング画面
     */
    public function index(): View
    {
        $rankedBooks = Book::query()
            ->has('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderBy('books.id')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
