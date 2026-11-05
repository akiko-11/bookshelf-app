<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use App\Services\ReadingPlanService;
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
     *
     * @param  BookIndexRequest  $request  検索条件を検証するリクエスト
     * @return AnonymousResourceCollection 書籍一覧のリソースコレクション
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

        // ジャンルが指定されていれば絞り込み
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
     * 書籍に紐づくジャンルとレビュー、
     * レビュー投稿者の情報を取得する。
     *
     * @param  Book  $book  詳細情報を取得する書籍
     * @return BookDetailResource 書籍詳細のリソース
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
     * バリデーション済みの書籍情報を保存し、
     * 指定されたジャンルを紐づける。
     * 登録した書籍情報をHTTP 201で返す。
     *
     * @param  StoreBookRequest  $request  書籍登録用の検証済みリクエスト
     * @return JsonResponse 登録した書籍情報を含むHTTP 201レスポンス
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // 登録者はSanctumで認証されたユーザーとする
        $validated['user_id'] = $request->user()->id;

        // ジャンルIDを別に保存
        $genreIds = $validated['genres'];

        // 書籍テーブルの登録対象からgenresを除外
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
     * 書籍の所有者のみ更新を許可する。
     * バリデーション済みの書籍情報を更新し、
     * 指定されたジャンルとの紐づけを置き換える。
     * 登録者IDは変更せず、既存の値を維持する。
     * 更新後の書籍情報をHTTP 200で返す。
     *
     * @param  UpdateBookRequest  $request  書籍更新用の検証済みリクエスト
     * @param  Book  $book  更新対象の書籍
     * @return JsonResponse 更新した書籍情報を含むHTTP 200レスポンス
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        // 書籍の所有者のみ更新を許可
        $this->authorize('update', $book);

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
     * 指定された書籍と関連データを物理削除する。
     *
     * 書籍の所有者のみ削除を許可する。
     * 関連する読書計画と通知はReadingPlanServiceで削除し、
     * その他の関連データは外部キー制約により削除する。
     * ジャンルマスタとユーザー情報は保持する。
     *
     * @param  Book  $book  削除対象の書籍
     * @param  ReadingPlanService  $readingPlanService  読書計画・通知の削除サービス
     * @return Response 削除成功時のHTTP 204レスポンス
     */
    public function destroy(
        Book $book,
        ReadingPlanService $readingPlanService
    ): Response {
        // 書籍の所有者のみ削除を許可
        $this->authorize('delete', $book);

        DB::transaction(function () use ($book, $readingPlanService): void {
            // 関連する読書計画と通知を削除
            foreach ($book->readingPlans as $plan) {
                $readingPlanService->deletePlan($plan);
            }

            // 書籍を削除
            $book->delete();
        });

        return response()->noContent();
    }
}
