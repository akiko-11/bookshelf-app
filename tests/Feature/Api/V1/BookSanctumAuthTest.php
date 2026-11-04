<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーは書籍を登録できない。
     */
    public function test_guest_cannot_create_book(): void
    {
        $this->postJson('/api/v1/books', [])
            ->assertUnauthorized();
    }

    /**
     * 未認証ユーザーは書籍を更新できない。
     */
    public function test_guest_cannot_update_book(): void
    {
        $book = Book::factory()->create();

        $this->putJson("/api/v1/books/{$book->id}", [])
            ->assertUnauthorized();
    }

    /**
     * 未認証ユーザーは書籍を削除できない。
     */
    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertUnauthorized();
    }

    /**
     * 有効なBearerトークンで書籍を登録できる。
     * 登録者IDは認証ユーザーのIDとなる。
     */
    public function test_authenticated_user_can_create_book_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        // 実際のSanctumトークンを生成
        $token = $user->createToken('test-token')->plainTextToken;

        $data = [
            'user_id' => $otherUser->id,
            'title' => 'Sanctum登録テスト',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-01',
            'genres' => [$genre->id],
        ];

        $this->withToken($token)
            ->postJson('/api/v1/books', $data)
            ->assertCreated();

        // リクエストのuser_idではなく認証ユーザーのIDが保存される
        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9781234567897',
        ]);
    }

    /**
     * 無効なBearerトークンでは書籍を登録できない。
     */
    public function test_invalid_bearer_token_cannot_create_book(): void
    {
        $this->withToken('invalid-token')
            ->postJson('/api/v1/books', [])
            ->assertUnauthorized();
    }

    /**
     * 書籍の所有者はBearerトークンで更新できる。
     */
    public function test_owner_can_update_book_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $genre = Genre::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $data = [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => '2026-10-01',
            'genres' => [$genre->id],
        ];

        $this->withToken($token)
            ->putJson("/api/v1/books/{$book->id}", $data)
            ->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
        ]);
    }

    /**
     * 書籍の所有者以外はBearerトークンで更新できない。
     */
    public function test_non_owner_cannot_update_book_with_bearer_token(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $data = [
            'title' => '不正な更新',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => '2026-10-01',
            'genres' => [$genre->id],
        ];

        $this->withToken($token)
            ->putJson("/api/v1/books/{$book->id}", $data)
            ->assertForbidden();

        // 他人による更新で書籍情報が変更されていないこと
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => $book->title,
        ]);
    }

    /**
     * 書籍の所有者はBearerトークンで削除できる。
     */
    public function test_owner_can_delete_book_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/v1/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * 書籍の所有者以外はBearerトークンで削除できない。
     */
    public function test_non_owner_cannot_delete_book_with_bearer_token(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create(['user_id' => $owner->id]);

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/v1/books/{$book->id}")
            ->assertForbidden();

        // 他人の削除操作では書籍が残ること
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }
}
