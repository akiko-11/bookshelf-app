<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍一覧を取得できる
    public function test_guest_can_get_book_list(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
    }

    // 書籍一覧がJSON形式で返される
    public function test_returns_book_list_as_json(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        $response->assertJsonPath('data.0.id', $book->id);

        $this->assertIsArray($response->json('data'));
    }

    // 書籍情報に必要な項目が含まれる
    public function test_returns_required_book_fields(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $book->genres()->attach($genre->id);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'image_url',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'reviews_avg_rating',
                    'reviews_count',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $response->assertJsonPath('data.0.id', $book->id);
        $response->assertJsonPath('data.0.title', $book->title);
        $response->assertJsonPath('data.0.genres.0.id', $genre->id);
        $response->assertJsonPath('data.0.genres.0.name', '小説');
    }

    // レビュー平均評価とレビュー件数が正しく取得できる
    public function test_returns_review_average_and_count(): void
    {
        $book = Book::factory()->create();

        Review::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '面白かったです',
        ]);

        Review::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'rating' => 2,
            'comment' => '普通でした',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonPath('data.0.reviews_count', 2);

        $this->assertEquals(
            3,
            $response->json('data.0.reviews_avg_rating')
        );
    }

    // キーワードでタイトルの部分一致検索ができる
    public function test_filters_books_by_keyword(): void
    {
        $targetBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'Python入門',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $targetBook->id);
        $response->assertJsonPath('data.0.title', 'Laravel入門');
    }

    // ジャンルIDで書籍を絞り込める
    public function test_filters_books_by_genre(): void
    {
        $genre1 = Genre::create(['name' => '小説']);
        $genre2 = Genre::create(['name' => '技術書']);

        $targetBook = Book::factory()->create([
            'title' => '小説の本',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '技術書の本',
        ]);

        $targetBook->genres()->attach($genre1->id);
        $otherBook->genres()->attach($genre2->id);

        $response = $this->getJson(
            "/api/v1/books?genre_id={$genre1->id}"
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $targetBook->id);
    }

    // キーワードとジャンルを組み合わせて検索できる
    public function test_filters_books_by_keyword_and_genre(): void
    {
        $genre1 = Genre::create(['name' => '技術書']);
        $genre2 = Genre::create(['name' => '小説']);

        $targetBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $otherBook1 = Book::factory()->create([
            'title' => 'Laravelの小説',
        ]);

        $otherBook2 = Book::factory()->create([
            'title' => 'Python入門',
        ]);

        $targetBook->genres()->attach($genre1->id);
        $otherBook1->genres()->attach($genre2->id);
        $otherBook2->genres()->attach($genre1->id);

        $response = $this->getJson(
            "/api/v1/books?keyword=Laravel&genre_id={$genre1->id}"
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $targetBook->id);
    }

    // 指定したページの書籍を取得できる
    public function test_returns_requested_page(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson(
            '/api/v1/books?page=2&per_page=2'
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    // 指定した1ページあたりの取得件数が反映される
    public function test_returns_specified_number_of_books_per_page(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson(
            '/api/v1/books?per_page=2'
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.last_page', 2);
    }

    // 検索結果が0件の場合は空配列が返される
    public function test_returns_empty_array_when_no_books_match(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $response = $this->getJson(
            '/api/v1/books?keyword=NoMatchingBookTitle123'
        );

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('meta.total', 0);
    }
}
