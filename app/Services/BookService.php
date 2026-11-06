<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class BookService
{
    public function __construct(
        private readonly ReadingPlanService $readingPlanService
    ) {}

    /**
     * 書籍を登録し、ジャンルを紐付ける。
     *
     * @param  array  $data  バリデーション済みの書籍情報
     * @param  int  $userId  登録者ID
     * @return Book 登録した書籍
     */
    public function createBook(array $data, int $userId): Book
    {
        // ジャンル情報を分離
        $genreIds = $data['genres'];

        // 書籍テーブルの登録対象から除外
        unset($data['genres'], $data['user_id']);

        // 登録者は認証ユーザーとする
        $data['user_id'] = $userId;

        return DB::transaction(function () use ($data, $genreIds): Book {
            // 書籍を登録
            $book = Book::create($data);

            // ジャンルを紐付ける
            $book->genres()->sync($genreIds);

            return $book;
        });
    }

    /**
     * 書籍情報とジャンルの紐付けを更新する。
     *
     * @param  Book  $book  更新対象の書籍
     * @param  array  $data  バリデーション済みの更新情報
     * @return Book 更新した書籍
     */
    public function updateBook(Book $book, array $data): Book
    {
        // ジャンル情報を分離
        $genreIds = $data['genres'] ?? null;

        // ジャンルと登録者IDを更新対象から除外
        unset($data['genres'], $data['user_id']);

        return DB::transaction(function () use ($book, $data, $genreIds): Book {
            // 書籍情報を更新
            $book->update($data);

            // ジャンルが指定されている場合のみ同期
            if ($genreIds !== null) {
                $book->genres()->sync($genreIds);
            }

            return $book;
        });
    }

    /**
     * 書籍と関連する読書計画・通知を削除する。
     *
     * @param  Book  $book  削除対象の書籍
     */
    public function deleteBook(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            // 関連する読書計画と通知を削除
            foreach ($book->readingPlans as $plan) {
                $this->readingPlanService->deletePlan($plan);
            }

            // 書籍を削除
            $book->delete();
        });
    }
}
