<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    // タイトルが未入力の場合は登録できない
    public function test_title_is_required_for_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'title' => '',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'title' => 'タイトルを入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // タイトルが255文字を超える場合は登録できない
    public function test_title_with_256_characters_fails_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'title' => str_repeat('あ', 256),
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'title' => 'タイトルは255文字以内で入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 著者名が未入力の場合は登録できない
    public function test_author_is_required_for_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'author' => '',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'author' => '著者名を入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 著者名が255文字を超える場合は登録できない
    public function test_author_with_256_characters_fails_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'author' => str_repeat('あ', 256),
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'author' => '著者名は255文字以内で入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが未入力の場合は登録できない
    public function test_isbn_is_required_for_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'isbn' => '',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNを入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが13桁でない場合は登録できない
    public function test_isbn_with_12_characters_fails_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'isbn' => '123456789012',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁で入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが重複している場合は登録できない
    public function test_duplicate_isbn_fails_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $data = $this->validBookData($genre->id);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'そのISBNは既に使用されています',
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    // 出版日が未入力の場合は登録できない
    public function test_published_date_is_required_for_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'published_date' => '',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'published_date' => '出版日を入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 出版日が不正な日付形式の場合は登録できない
    public function test_informal_published_date_fails_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'published_date' => 'invalid-date',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'published_date' => '出版日は有効な日付形式で入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 未来の出版日でも登録できる
    public function test_future_published_date_success_book_registration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00'));

        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'published_date' => '2026-10-05',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $this->assertDatabaseHas('books', [
            'title' => 'テストの本',
            'published_date' => '2026-10-05',
            'user_id' => $user->id,
        ]);

        Carbon::setTestNow();
    }

    // 画像URLがURL形式でない場合は登録できない
    public function test_informal_image_url_fails_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'image_url' => 'wrong_url',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは有効なURL形式で入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 画像URLが255文字を超える場合は登録できない
    public function test_image_url_with_256_characters_fails_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'image_url' => 'https://example.com/'.str_repeat('a', 236),
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは255文字以内で入力してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // ジャンルが未選択の場合は登録できない
    public function test_genres_is_required_for_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        unset($data['genres']);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルは1つ以上選択してください',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 存在しないジャンルIDでは登録できない
    public function test_genres_without_exsiting_genre_id_fails_book_registration(): void
    {
        $user = User::factory()->create();

        $data = $this->validBookData(99999);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'genres.0' => '選択されたジャンルが存在しません',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 同じジャンルを重複指定した場合は登録できない
    public function test_duplicate_genres_fail_book_registration(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'genres' => [$genre->id, $genre->id],
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'genres.0' => '同じジャンルを重複して選択することはできません',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // 正常な書籍登録データを返す
    private function validBookData(int $genreId, array $overrides = []): array
    {
        return array_merge([
            'title' => 'テストの本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/test.jpg',
            'genres' => [$genreId],
        ], $overrides);
    }
}
