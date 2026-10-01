<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    // 書籍を登録したユーザーを取得できる
    public function test_book_belongs_to_user(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $bookUser = $book->user;

        $this->assertTrue($bookUser->is($user));
    }

    // 書籍に紐づくジャンルを取得できる
    public function test_book_has_genres(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book->genres()->attach($genre->id);

        $genres = $book->genres;

        $this->assertTrue($genres->contains($genre));
    }

    // 書籍に投稿されたレビューを取得できる
    public function test_book_has_reviews(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $review = $this->createReview($user, $book);

        $reviews = $book->reviews;

        $this->assertTrue($reviews->contains($review));
    }

    // 書籍に紐づくお気に入り情報を取得できる
    public function test_book_has_favorites(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $user->favoriteBooks()->attach($book->id);

        $favorites = $book->favorites;

        $this->assertTrue(
            $favorites->contains('user_id', $user->id)
        );
    }

    // ユーザー作成
    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'テストユーザー',
        ]);
    }

    // 書籍データ作成
    private function createBook(User $user): Book
    {
        return Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'テスト用の説明です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);
    }

    // レビュー作成
    private function createReview(User $user, Book $book): Review
    {
        return Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => 'これはテストです。',
        ]);
    }
}
