<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    // 存在しない書籍IDの場合は404と日本語メッセージを返す
    public function test_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
        $response->assertJsonPath(
            'message',
            '指定された書籍が見つかりません。'
        );
    }

    // ゲストでも書籍詳細を取得できる
    public function test_guest_can_get_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', $book->title);
    }

    // 書籍詳細に必要な基本情報が含まれる
    public function test_returns_required_book_fields(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
            ],
        ]);
    }

    // 書籍に紐づくジャンル情報が正しく返される
    public function test_returns_book_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.genres.0.name', $genre->name);
    }

    // 書籍に紐づくレビュー情報と投稿者名が正しく返される
    public function test_returns_book_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.reviews.0.id', $review->id);
        $response->assertJsonPath('data.reviews.0.user_name', $user->name);
        $response->assertJsonPath('data.reviews.0.rating', $review->rating);
        $response->assertJsonPath('data.reviews.0.comment', $review->comment);
        $response->assertJsonPath('data.reviews.0.created_at', $review->created_at->format('Y-m-d H:i:s'));
    }

    // レビューが投稿日時の降順で返される
    public function test_returns_reviews_in_descending_date_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::factory()->create();

        // 古いレビューを先に作成
        $oldReview = Review::factory()->create([
            'user_id' => $userA->id,
            'book_id' => $book->id,
            'created_at' => '2026-10-01 10:00:00',
        ]);
        $newReview = Review::factory()->create([
            'user_id' => $userB->id,
            'book_id' => $book->id,
            'created_at' => '2026-10-10 10:00:00',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.reviews.0.id', $newReview->id);
        $response->assertJsonPath('data.reviews.1.id', $oldReview->id);
    }

    // レビューが0件の場合は空配列が返される
    public function test_returns_empty_reviews_when_no_reviews_exist(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.reviews', []);
    }
}
