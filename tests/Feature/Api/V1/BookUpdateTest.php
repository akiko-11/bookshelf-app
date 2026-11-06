<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    // 所有者は書籍を更新できる
    public function test_owner_can_update_book(): void
    {
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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
        $user = $this->createAuthenticatedUser();

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

    /**
     * 未認証ユーザーは書籍を更新できず、
     * 401と指定されたJSONメッセージが返される。
     */
    public function test_guest_cannot_update_book(): void
    {
        $book = Book::factory()->create([
            'title' => '変更前のタイトル',
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            ['title' => '変更後のタイトル']
        );

        // HTTP 401とJSONメッセージを確認
        $response->assertUnauthorized();

        $response->assertExactJson([
            'message' => '認証が必要です。',
        ]);

        $response->assertHeader(
            'Content-Type',
            'application/json'
        );

        // 書籍が更新されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更前のタイトル',
        ]);
    }

    /**
     * 無効なBearerトークンでは書籍を更新できず、
     * 401と指定されたJSONメッセージが返される。
     */
    public function test_invalid_token_cannot_update_book(): void
    {
        // 更新対象の書籍を作成
        $book = Book::factory()->create([
            'title' => '変更前のタイトル',
        ]);

        // 無効なBearerトークンで更新リクエスト
        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            ['title' => '変更後のタイトル'],
            [
                'Authorization' => 'Bearer invalid-token',
            ]
        );

        // HTTP 401とJSONメッセージを確認
        $response->assertUnauthorized();

        $response->assertExactJson([
            'message' => '認証が必要です。',
        ]);

        $response->assertHeader(
            'Content-Type',
            'application/json'
        );

        // 書籍が更新されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更前のタイトル',
        ]);
    }

    /**
     * 認証済みでも他人の書籍は更新できず、
     * 403と指定されたJSONメッセージが返される。
     */
    public function test_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        // 所有者の書籍を作成
        $book = Book::factory()->for($owner)->create([
            'title' => '変更前のタイトル',
        ]);

        // 別ユーザーのBearerトークンで認証
        $token = $otherUser->createToken('test-token')->plainTextToken;
        $this->withToken($token);

        // バリデーションを通過する更新データ
        $data = $this->validBookData();

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        // HTTP 403と指定されたJSONを確認
        $response->assertForbidden();

        $response->assertExactJson([
            'message' => 'この書籍を操作する権限がありません。',
        ]);

        $response->assertHeader(
            'Content-Type',
            'application/json'
        );

        // DBの書籍情報が変更されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '変更前のタイトル',
        ]);
    }

    /**
     * 所有者以外が不正な更新データを送信しても、
     * バリデーションより認可が優先され403を返す。
     */
    public function test_non_owner_cannot_update_book_with_invalid_data(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->for($owner)->create([
            'title' => '変更前のタイトル',
        ]);

        // 更新前のジャンルを登録
        $originalGenre = Genre::factory()->create();
        $book->genres()->attach($originalGenre->id);

        // 所有者以外のBearerトークンで認証
        $token = $otherUser->createToken('test-token')->plainTextToken;
        $this->withToken($token);

        // 不正な更新データを用意
        $data = $this->validBookData();
        $data['title'] = '';

        // APIへ更新リクエスト
        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        // バリデーションの422より認可の403が優先される
        $response->assertForbidden();

        $response->assertExactJson([
            'message' => 'この書籍を操作する権限がありません。',
        ]);

        // 書籍の情報が変更されていない
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '変更前のタイトル',
        ]);

        // ジャンルの関連付けも変更されていない
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $originalGenre->id,
        ]);

        $this->assertDatabaseCount('book_genre', 1);
    }

    /**
     * ジャンル同期時に例外が発生した場合、
     * 書籍情報とジャンル紐付けが更新前のままである。
     */
    public function test_book_and_genres_are_rolled_back_when_genre_sync_fails(): void
    {
        $user = $this->createAuthenticatedUser();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
        ]);

        // 更新前のジャンル
        $genreA = Genre::factory()->create();

        $book->genres()->attach($genreA->id);

        // 更新後のジャンル
        $genreB = Genre::factory()->create();

        $data = $this->validBookData();

        $data['title'] = '更新後タイトル';
        $data['genres'] = [$genreB->id];

        // ジャンル同期時に意図的に例外を発生させる
        DB::listen(function (QueryExecuted $query): void {
            if (preg_match('/insert\s+into\s+[`"]?book_genre[`"]?/i', $query->sql)) {
                throw new \RuntimeException('ジャンル同期中のテスト用例外');
            }
        });

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertStatus(500);

        // 書籍情報が更新前のまま
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);

        // 更新後のタイトルになっていない
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);

        // 更新前のジャンル紐付けが残っている
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genreA->id,
        ]);

        // 更新後のジャンル紐付けが存在しない
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genreB->id,
        ]);
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

    /**
     * テスト用ユーザーを作成し、Bearerトークンで認証する。
     */
    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token);

        return $user;
    }
}
