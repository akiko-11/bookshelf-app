<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍を更新できる
    public function test_guest_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = $this->validBookData();

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
        ]);
    }

    // 書籍更新時に既存のジャンルが指定したジャンルに置き換わる
    public function test_update_replaces_book_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genreA = Genre::factory()->create();

        $book->genres()->attach($genreA->id);

        $genreB = Genre::factory()->create();
        $genreC = Genre::factory()->create();

        $data = $this->validBookData();

        $data['genres'] = [$genreB->id, $genreC->id];

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $genres = $book->fresh()->genres;

        $this->assertCount(2, $genres);
        $this->assertFalse($genres->contains('id', $genreA->id));
        $this->assertTrue($genres->contains('id', $genreB->id));
        $this->assertTrue($genres->contains('id', $genreC->id));
    }

    // 存在しない書籍IDの場合は404エラーになる
    public function test_returns_404_when_book_not_found(): void
    {
        $data = $this->validBookData();

        $response = $this->putJson(
            '/api/v1/books/99999',
            $data
        );

        $response->assertStatus(404);

        $response->assertJsonPath(
            'message',
            '指定された書籍が見つかりません。'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 更新した書籍情報がJSON形式で返される
    public function test_returns_updated_book_as_json(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = $this->validBookData();

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', $data['title']);
        $response->assertJsonPath('data.author', $data['author']);

        $response->assertJsonPath('data.genres.0.id', $data['genres'][0]);

        $response->assertJsonPath('data.reviews_count', 0);
        $response->assertJsonPath('data.reviews_avg_rating', null);
    }

    // 自分自身のISBNを変更せずに書籍を更新できる
    public function test_can_update_book_with_same_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = $this->validBookData();

        $data['isbn'] = $book->isbn;

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    // 説明文と画像URLをnullに更新できる
    public function test_can_update_book_with_null_optional_fields(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'description' => '更新前の説明文',
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $data = $this->validBookData();

        $data['description'] = null;
        $data['image_url'] = null;

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => null,
            'image_url' => null,
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    // 説明文と画像URLを省略しても書籍を更新できる
    public function test_can_update_book_without_optional_fields(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'description' => '更新前の説明文',
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $data = $this->validBookData();

        unset($data['description']);
        unset($data['image_url']);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => '更新前の説明文',
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    // 異なる登録者IDを送信しても元の登録者IDが維持される
    public function test_update_does_not_change_user_id(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherUser = User::factory()->create();

        $data = $this->validBookData();

        $data['user_id'] = $otherUser->id;

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    // 書籍登録用の正常なリクエストデータを作成
    private function validBookData(): array
    {
        $genre = Genre::factory()->create();

        return [
            'title' => '公開APIの更新',
            'author' => 'テスト著者',
            'isbn' => '9784567890124',
            'published_date' => '2026-10-10',
            'description' => '公開APIから更新した書籍です。',
            'image_url' => 'https://example.com',
            'genres' => [$genre->id],
        ];
    }
}
