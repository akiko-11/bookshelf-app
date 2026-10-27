<?php

namespace Tests\Feature\Api\V1;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    // 必須項目が未入力の場合はエラーになる
    public function test_rejects_missing_required_fields(): void
    {
        $data = [];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'user_id',
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);

        $response->assertJsonPath(
            'errors.user_id.0',
            '登録者IDは必須です'
        );

        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは必須です'
        );

        $response->assertJsonPath(
            'errors.author.0',
            '著者名は必須です'
        );

        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNは必須です'
        );
        $response->assertJsonPath(
            'errors.published_date.0',
            '出版日は必須です'
        );

        $response->assertJsonPath(
            'errors.genres.0',
            'ジャンルを1つ以上選択してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ユーザーIDが整数以外の場合はエラーになる
    public function test_rejects_non_integer_user_id(): void
    {
        $data = $this->validBookData();

        $data['user_id'] = 'string';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);

        $response->assertJsonPath(
            'errors.user_id.0',
            '登録者IDは整数で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 存在しないユーザーIDの場合はエラーになる
    public function test_rejects_nonexistent_user_id(): void
    {
        $data = $this->validBookData();

        $data['user_id'] = 99999;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);

        $response->assertJsonPath(
            'errors.user_id.0',
            '指定されたユーザーが存在しません'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが13桁以外の場合はエラーになる
    public function test_rejects_invalid_isbn(): void
    {
        $data = $this->validBookData();

        // 12桁
        $data['isbn'] = '123456789012';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['isbn']);

        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNは13桁で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが13桁でも数字以外を含む場合はエラーになる
    public function test_rejects_non_numeric_isbn(): void
    {
        $data = $this->validBookData();

        $data['isbn'] = 'abc123efg4567';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['isbn']);

        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNは13桁で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが重複する場合はエラーになる
    public function test_rejects_duplicate_isbn(): void
    {
        $data = $this->validBookData();

        // 1回目のPOST（登録成功）
        $this->postJson('/api/v1/books', $data)->assertCreated();

        // 2回目のPOST（同じISBN）
        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['isbn']);

        $response->assertJsonPath(
            'errors.isbn.0',
            'そのISBNは既に使用されています'
        );

        $this->assertDatabaseCount('books', 1);
    }

    // 存在しないジャンルIDの場合はエラーになる
    public function test_rejects_nonexistent_genre_id(): void
    {
        $data = $this->validBookData();

        $data['genres'] = [99999];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres.0']);

        $this->assertSame(
            '選択されたジャンルが存在しません',
            $response->json('errors')['genres.0'][0]
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 同じジャンルIDを重複指定した場合はエラーになる
    public function test_rejects_duplicate_genre_ids(): void
    {
        $data = $this->validBookData();

        $genreId = $data['genres'][0];
        $data['genres'] = [$genreId, $genreId];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres.0']);

        $this->assertSame(
            '同じジャンルを重複して選択することはできません',
            $response->json('errors')['genres.0'][0]
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ジャンルが未選択の場合はエラーになる
    public function test_rejects_empty_genres(): void
    {
        $data = $this->validBookData();

        $data['genres'] = [];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres']);

        $this->assertSame(
            'ジャンルを1つ以上選択してください',
            $response->json('errors')['genres'][0]
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ジャンルが配列以外の場合はエラーになる
    public function test_rejects_genres_not_array(): void
    {
        $data = $this->validBookData();

        $data['genres'] = 'abc';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres']);

        $this->assertSame(
            'ジャンルの形式が正しくありません',
            $response->json('errors')['genres'][0]
        );

        $this->assertDatabaseCount('books', 0);
    }

    // ジャンルIDが整数以外の場合はエラーになる
    public function test_rejects_non_integer_genre_id(): void
    {
        $data = $this->validBookData();

        $data['genres'] = ['abc'];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres.0']);

        $this->assertSame(
            'ジャンルIDは整数で入力してください',
            $response->json('errors')['genres.0'][0]
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 出版日が不正な形式の場合はエラーになる
    public function test_rejects_invalid_published_date(): void
    {
        $data = $this->validBookData();

        $data['published_date'] = 'invalid-date';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['published_date']);

        $response->assertJsonPath(
            'errors.published_date.0',
            '出版日は有効な日付形式で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 画像URLが不正な形式の場合はエラーになる
    public function test_rejects_invalid_image_url(): void
    {
        $data = $this->validBookData();

        $data['image_url'] = 'invalid-image_url';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image_url']);

        $response->assertJsonPath(
            'errors.image_url.0',
            '画像URLは有効なURL形式で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // タイトルが文字列以外の場合はエラーになる
    public function test_rejects_non_string_title(): void
    {
        $data = $this->validBookData();

        $data['title'] = 123;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);

        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは文字列で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // タイトルが255文字を超える場合はエラーになる
    public function test_rejects_title_exceeding_max_length(): void
    {
        $data = $this->validBookData();

        $data['title'] = str_repeat('あ', 256);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);

        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは255文字以内で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 著者名が文字列以外の場合はエラーになる
    public function test_rejects_non_string_author(): void
    {
        $data = $this->validBookData();

        $data['author'] = 123;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['author']);

        $response->assertJsonPath(
            'errors.author.0',
            '著者名は文字列で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 著者名が255文字を超える場合はエラーになる
    public function test_rejects_author_exceeding_max_length(): void
    {
        $data = $this->validBookData();

        $data['author'] = str_repeat('あ', 256);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['author']);

        $response->assertJsonPath(
            'errors.author.0',
            '著者名は255文字以内で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 画像URLが255文字を超える場合はエラーになる
    public function test_rejects_image_url_exceeding_max_length(): void
    {
        $data = $this->validBookData();

        $data['image_url'] = 'https://example.com/'.str_repeat('a', 236);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image_url']);

        $response->assertJsonPath(
            'errors.image_url.0',
            '画像URLは255文字以内で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 説明文が文字列以外の場合はエラーになる
    public function test_rejects_non_string_description(): void
    {
        $data = $this->validBookData();

        $data['description'] = 123;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);

        $response->assertJsonPath(
            'errors.description.0',
            '説明は文字列で入力してください'
        );

        $this->assertDatabaseCount('books', 0);
    }

    // 書籍登録用の正常なリクエストデータを作成
    private function validBookData(): array
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        return [
            'user_id' => $user->id,
            'title' => '公開APIの新規登録',
            'author' => 'テスト著者',
            'isbn' => '9784567890124',
            'published_date' => '2026-10-01',
            'description' => '公開APIから新規登録した書籍です。',
            'image_url' => 'https://example.com',
            'genres' => [$genre->id],
        ];
    }
}
