<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍を物理削除できる
    public function test_guest_can_destroy_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    // 書籍削除時にジャンルとの関連付けが削除される
    public function test_destroy_book_detaches_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    // 書籍削除時にレビューが削除され、投稿者は維持される
    public function test_destroy_book_deletes_reviews_but_keeps_users(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $reviewer->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    // 書籍削除時にレビューのいいね情報も削除される
    public function test_destroy_book_deletes_review_likes(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $liker = User::factory()->create();

        $reviewLike = ReviewLike::create([
            'user_id' => $liker->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'id' => $reviewLike->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);
    }

    // 書籍削除時にお気に入り情報が削除される
    public function test_destroy_book_deletes_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $favoriteUser = User::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $favoriteUser->id,
        ]);
    }

    // 存在しない書籍の削除で404を返す
    public function test_destroy_nonexistent_book_returns_404(): void
    {
        $response = $this->deleteJson(
            '/api/v1/books/99999'
        );

        $response->assertStatus(404);

        $response->assertExactJson([
            'message' => '指定された書籍が見つかりません。',
        ]);
    }
}
