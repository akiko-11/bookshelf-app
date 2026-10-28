<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBookTest extends TestCase
{
    use RefreshDatabase;

    // 作成者本人は書籍情報を更新できる
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-10-12',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );
    }

    // 書籍に紐付くジャンルを更新できる
    public function test_book_genres_are_updated(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $oldGenre = Genre::create([
            'name' => '更新前ジャンル',
        ]);

        $newGenre = Genre::create([
            'name' => '更新後ジャンル',
        ]);

        $book->genres()->attach($oldGenre->id);

        $data = $this->validBookData($newGenre->id);

        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            $data
        );

        $updatedBook = $book->fresh();

        $this->assertTrue(
            $updatedBook->genres->contains($newGenre->id)
        );

        $this->assertFalse(
            $updatedBook->genres->contains($oldGenre->id)
        );

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );
    }

    // 他ユーザーは書籍を更新できない
    public function test_other_user_cannot_update_book(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $targetUser->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $data = $this->validBookData($genre->id);

        $original = $book->fresh()->getAttributes();

        $response = $this->actingAs($otherUser)->put(
            route('books.update', $book),
            $data
        );

        $response->assertForbidden();

        $this->assertSame($original, $book->fresh()->getAttributes());

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 未認証ユーザーは書籍を更新できない
    public function test_guest_cannot_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $data = $this->validBookData($genre->id);

        $original = $book->fresh()->getAttributes();

        $response = $this->put(
            route('books.update', $book),
            $data
        );

        $response->assertRedirect(route('login'));

        $this->assertSame($original, $book->fresh()->getAttributes());

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 他ユーザーが不正な入力で書籍を更新しようとしても403になる
    public function test_other_user_cannot_update_book_with_invalid_data(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $data = $this->validBookData($genre->id);
        $data['title'] = '';

        $original = $book->fresh()->getAttributes();

        $response = $this->actingAs($otherUser)->put(
            route('books.update', $book),
            $data
        );

        $response->assertForbidden();

        $this->assertSame($original, $book->fresh()->getAttributes());

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 書籍更新用のデータ
    private function validBookData(int $genreId): array
    {
        return [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-10-12',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [$genreId],
        ];
    }
}
