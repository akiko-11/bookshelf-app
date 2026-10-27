<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧を検索条件に応じて取得する。
     *
     * キーワード検索、ジャンル絞り込み、ページネーションに対応する。
     *
     * @return AnonymousResourceCollection
     */
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

    /**
     * 指定された書籍の詳細情報を取得する。
     *
     * 書籍に紐づくジャンルとレビュー、レビュー投稿者の情報を取得する。
     *
     * @return BookDetailResource
     */
    public function show(Book $book)
    {
        // 書籍に関するジャンル・レビュー・投稿者を取得
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->with('user')
                    ->orderByDesc('created_at');
            },
        ]);

        return new BookDetailResource($book);
    }

    /**
     * 新しい書籍を登録する。
     *
     * バリデーション済みの書籍情報を保存し、指定されたジャンルを紐づける。
     * 登録した書籍情報をHTTP 201で返す。
     *
     * @return JsonResponse
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        // ジャンルIDを別に保存
        $genreIds = $validated['genres'];

        // 書籍テーブルに存在しないgenresを除外
        unset($validated['genres']);

        // 書籍登録とジャンルの紐づけを一括で実行
        $book = DB::transaction(function () use ($validated, $genreIds) {
            $book = Book::create($validated);

            $book->genres()->attach($genreIds);

            return $book;
        });

        // レスポンスに必要な関連情報を取得
        $book->load('genres')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return (new BookResource($book))->response()->setStatusCode(201);
    }
}
