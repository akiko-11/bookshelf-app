<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBookValidationTest extends TestCase
{
    use RefreshDatabase;

    // タイトルが未入力の場合は更新できない
    public function test_title_is_required_for_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'title' => '',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'title' => 'タイトルを入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }

    // タイトルが255文字を超える場合は更新できない
    public function test_title_with_256_characters_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'title' => str_repeat('あ', 256),
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'title' => 'タイトルは255文字以内で入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }

    // 著者名が未入力の場合は更新できない
    public function test_author_is_required_for_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'author' => '',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'author' => '著者名を入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'author' => $book->author,
        ]);
    }

    // 著者名が255文字を超える場合は更新できない
    public function test_author_with_256_characters_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'author' => str_repeat('あ', 256),
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'author' => '著者名は255文字以内で入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'author' => $book->author,
        ]);
    }

    // ISBNが未入力の場合は更新できない
    public function test_isbn_is_required_for_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'isbn' => '',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNを入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

    // ISBNが13桁でない場合は更新できない
    public function test_isbn_with_12_characters_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'isbn' => '123456789012',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁で入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

    // 他の書籍とISBNが重複している場合は更新できない
    public function test_duplicate_isbn_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);

        Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'isbn' => '1234567890123',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'isbn' => 'そのISBNは既に使用されています',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '1111111111111',
        ]);
    }

    // 現在の書籍自身のISBNはそのまま使用できる
    public function test_current_isbn_can_be_used_for_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'isbn' => $book->isbn,
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionDoesntHaveErrors('isbn');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '1234567890123',
        ]);
    }

    // 出版日が未入力の場合は更新できない
    public function test_published_date_is_required_for_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'published_date' => '',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'published_date' => '出版日を入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 出版日が不正な日付形式の場合は更新できない
    public function test_invalid_published_date_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'published_date' => 'invalid-date',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'published_date' => '出版日は有効な日付形式で入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 未来の出版日でも更新できる
    public function test_future_published_date_success_book_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00'));

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'published_date' => '2026-10-05',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'published_date' => '2026-10-05',
        ]);

        Carbon::setTestNow();
    }

    // 画像URLがURL形式でない場合は更新できない
    public function test_invalid_image_url_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'image_url' => 'wrong_url',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは有効なURL形式で入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 画像URLが255文字を超える場合は更新できない
    public function test_image_url_with_256_characters_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'image_url' => 'https://example.com/'.str_repeat('a', 236),
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは255文字以内で入力してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // ジャンルが未選択の場合は更新できない
    public function test_genres_is_required_for_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        unset($data['genres']);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルは1つ以上選択してください',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 存在しないジャンルIDでは更新できない
    public function test_non_existing_genre_id_fails_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = $this->validBookData(99999);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'genres.0' => '選択されたジャンルが存在しません',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 同じジャンルを重複指定した場合は更新できない
    public function test_duplicate_genres_fail_book_update(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'genres' => [$genre->id, $genre->id],
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'genres.0' => '同じジャンルを重複して選択することはできません',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // バリデーションエラー時に入力内容が保持される
    public function test_input_is_preserved_after_validation_error(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id, [
            'title' => '',
            'author' => '入力途中の著者名',
        ]);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $response->assertSessionHasErrors([
            'title' => 'タイトルを入力してください',
        ]);

        $response->assertSessionHasInput(
            'author',
            '入力途中の著者名'
        );
    }

    // 正常な書籍更新データを返す
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
