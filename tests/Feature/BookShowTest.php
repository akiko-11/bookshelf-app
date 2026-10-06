<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍詳細画面を表示できる
    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create([
            'title' => 'テストの本',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テストの本');
    }

    // 書籍の基本情報が表示される
    public function test_book_detail_can_show(): void
    {
        $book = Book::factory()->create([
            'title' => 'テストの本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-03',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/test.jpg',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テストの本');
        $response->assertSee('テスト著者');
        $response->assertSee('1234567890123');
        $response->assertSee('2026-10-03');
        $response->assertSee('テスト説明');
        $response->assertSee('https://example.com/test.jpg');
    }

    // 書籍に紐づくジャンルが表示される
    public function test_book_detail_displays_genre(): void
    {
        $book = Book::factory()->create([
            'title' => 'テストの本',
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->sync([$genre->id]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テストジャンル');
    }

    // 書籍に紐づくレビュー情報といいね数が表示される
    public function test_book_detail_displays_reviews_and_likes(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'テストの本',
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '書籍に紐づくレビュー情報といいね数が表示されました',
        ]);

        $review->likedByUsers()->attach($user->id);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('★★★★☆');
        $response->assertSee('書籍に紐づくレビュー情報といいね数が表示されました');
        $response->assertSee('いいね (1)');
    }
}
