<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧を検索条件に応じて取得する。
     *
     * キーワード検索、ジャンル絞り込み、ページネーションに対応する。
     */
    public function index(BookIndexRequest $request): AnonymousResourceCollection
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
     */
    public function show(Book $book): BookDetailResource
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
     */
    public function store(StoreBookRequest $request): JsonResponse
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

    /**
     * 指定された書籍情報を更新する。
     *
     * バリデーション済みの書籍情報を更新し、
     * 指定されたジャンルとの紐づけを置き換える。
     * 登録者IDは変更せず、既存の値を維持する。
     * 更新後の書籍情報をHTTP 200で返す。
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $validated = $request->validated();

        // ジャンルIDを別に保存
        $genreIds = $validated['genres'] ?? null;

        // 書籍テーブルの更新対象から除外
        unset($validated['genres'], $validated['user_id']);

        // 書籍更新とジャンルの紐づけを一括で実行
        $book = DB::transaction(function () use ($book, $validated, $genreIds) {
            $book->update($validated);

            if ($genreIds !== null) {
                $book->genres()->sync($genreIds);
            }

            return $book;
        });

        // レスポンスに必要な関連情報を取得
        $book->load('genres')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        // 更新後の書籍情報を返す
        return (new BookResource($book))->response()->setStatusCode(200);
    }

    /**
     * 指定された書籍を物理削除する。
     *
     * 書籍に紐づくレビュー、いいね、お気に入り、
     * ジャンルとの関連付けを削除する。
     * ジャンルマスタとユーザー情報は保持する。
     */
    public function destroy(Book $book): Response
    {
        // 書籍を物理削除（関連データは外部キー制約により自動削除）
        $book->delete();

        return response()->noContent();
    }
}
