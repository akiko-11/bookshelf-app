<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    public function index(BookIndexRequest $request)
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 20);

        // 書籍を取得する条件を準備
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // キーワードがあれば検索条件を追加
        if (isset($validated['keyword'])) {
            $keyword = $validated['keyword'];

            $query->where('title', 'like', "%{$keyword}%");
        }

        // ジャンルが指定されていれば検索条件を追加
        if (! empty($validated['genre_id'])) {
            $genreId = $validated['genre_id'];

            $query->whereHas('genres', function ($query) use ($genreId) {
                $query->where('genres.id', $genreId);
            });
        }

        // 条件に合う書籍を指定件数ずつ取得
        $books = $query->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }
}
