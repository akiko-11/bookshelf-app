<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

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
     * 書籍を登録し、HTTP 201で結果を返す。
     *
     * @param  StoreBookRequest  $request  書籍登録リクエスト
     * @param  BookService  $bookService  書籍操作サービス
     * @return JsonResponse 登録結果
     */
    public function store(
        StoreBookRequest $request,
        BookService $bookService
    ): JsonResponse {
        // Serviceで書籍登録とジャンル紐付けを実行
        $book = $bookService->createBook(
            $request->validated(),
            $request->user()->id
        );

        // APIレスポンスに必要な関連情報を取得
        $book->load('genres')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍情報を更新し、HTTP 200で結果を返す。
     *
     * @param  UpdateBookRequest  $request  書籍更新リクエスト
     * @param  Book  $book  更新対象の書籍
     * @param  BookService  $bookService  書籍操作サービス
     * @return JsonResponse 更新結果
     */
    public function update(
        UpdateBookRequest $request,
        Book $book,
        BookService $bookService
    ): JsonResponse {
        // 書籍の所有者認可
        $this->authorize('update', $book);

        // Serviceで書籍とジャンルを更新
        $book = $bookService->updateBook(
            $book,
            $request->validated()
        );

        // APIレスポンスに必要な関連情報を取得
        $book->load('genres')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * 指定された書籍と関連データを物理削除する。
     *
     * @param  Book  $book  削除対象の書籍
     * @param  BookService  $bookService  書籍操作サービス
     * @return Response 削除成功時のHTTP 204レスポンス
     */
    public function destroy(
        Book $book,
        BookService $bookService
    ): Response {
        // 書籍の所有者認可
        $this->authorize('delete', $book);

        // Serviceで書籍と関連データを削除
        $bookService->deleteBook($book);

        return response()->noContent();
    }
}
