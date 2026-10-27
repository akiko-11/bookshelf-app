<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍を新規登録できる
    public function test_guest_can_store_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($user->id, [$genre->id]);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => '公開APIの新規登録',
            'author' => 'テスト著者',
            'isbn' => '9784567890124',
            'published_date' => '2026-10-01',
            'description' => '公開APIから新規登録した書籍です。',
            'image_url' => 'https://example.com',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'テストジャンル',
        ]);
    }

    // 新規登録した書籍に複数のジャンルが正しく紐づく
    public function test_store_book_with_genres(): void
    {
        $user = User::factory()->create();
        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        $data = $this->validBookData(
            $user->id,
            [$genreA->id, $genreB->id]
        );

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(201);
        $book = Book::where('isbn', $data['isbn'])->firstOrFail();

        $this->assertCount(2, $book->genres);

        $this->assertTrue($book->genres->contains('id', $genreA->id));

        $this->assertTrue($book->genres->contains('id', $genreB->id));
    }

    // 登録した書籍情報がJSON形式で返される
    public function test_returns_created_book_as_json(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = $this->validBookData($user->id, [$genre->id]);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(201);

        $response->assertJsonPath('data.title', $data['title']);

        $response->assertJsonPath('data.genres.0.id', $genre->id);

        $response->assertJsonPath('data.reviews_count', 0);
        $response->assertJsonPath('data.reviews_avg_rating', null);
    }

    // 説明文と画像URLがnullでも書籍を登録できる
    public function test_can_store_book_with_null_optional_fields(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = $this->validBookData($user->id, [$genre->id]);

        $data['description'] = null;
        $data['image_url'] = null;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(201);

        $this->assertDatabaseCount('books', 1);

        $this->assertDatabaseHas('books', [
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => null,
            'image_url' => null,
        ]);
    }

    // 説明文と画像URLを省略しても書籍を登録できる
    public function test_can_store_book_without_optional_fields(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = $this->validBookData($user->id, [$genre->id]);

        unset($data['description'], $data['image_url']);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(201);

        $this->assertDatabaseCount('books', 1);

        $this->assertDatabaseHas('books', [
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => null,
            'image_url' => null,
        ]);
    }

    // 書籍登録用の正常なリクエストデータを作成
    private function validBookData(int $userId, array $genreIds): array
    {
        return [
            'user_id' => $userId,
            'title' => '公開APIの新規登録',
            'author' => 'テスト著者',
            'isbn' => '9784567890124',
            'published_date' => '2026-10-01',
            'description' => '公開APIから新規登録した書籍です。',
            'image_url' => 'https://example.com',
            'genres' => $genreIds,
        ];
    }
}
