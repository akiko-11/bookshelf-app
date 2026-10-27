<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ユーザーに紐づく書籍を取得できる
    public function test_user_has_books(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $books = $user->books;

        $this->assertTrue($books->contains($book));
    }

    // ユーザーが投稿したレビューを取得できる
    public function test_user_has_reviews(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $review = $this->createReview($user, $book);

        $reviews = $user->reviews;

        $this->assertTrue($reviews->contains($review));
    }

    // ユーザーがお気に入り登録した書籍を取得できる
    public function test_user_has_favorite_books(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $user->favoriteBooks()->attach($book->id);

        $favoriteBooks = $user->favoriteBooks;

        $this->assertTrue($favoriteBooks->contains($book));
    }

    // ユーザーがいいねしたレビューを取得できる
    public function test_user_has_liked_reviews(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $user->likedReviews()->attach($review->id);

        $likedReviews = $user->likedReviews;

        $this->assertTrue($likedReviews->contains($review));
    }

    // ユーザーのお気に入り情報を取得できる
    public function test_user_has_many_favorites(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $favorites = $user->favorites;

        $this->assertTrue($favorites->contains($favorite));
    }

    // ユーザーが登録したレビューのいいね情報を取得できる
    public function test_user_has_many_review_likes(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $reviewLike = ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $reviewLikes = $user->reviewLikes;

        $this->assertTrue($reviewLikes->contains($reviewLike));
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
