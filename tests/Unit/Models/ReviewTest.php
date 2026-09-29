<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    // レビューを投稿したユーザーを取得できる
    public function test_review_belongs_to_user(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $reviewUser = $review->user;

        $this->assertTrue($reviewUser->is($user));
    }

    // レビュー対象の書籍を取得できる
    public function test_review_belongs_to_book(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $reviewBook = $review->book;

        $this->assertTrue($reviewBook->is($book));
    }

    // レビューにいいねしたユーザーを取得できる
    public function test_review_has_liked_users(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $likedUser = User::factory()->create();

        $likedUser->likedReviews()->attach($review->id);

        $likedUsers = $review->likedUsers;

        $this->assertTrue($likedUsers->contains($likedUser));
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
