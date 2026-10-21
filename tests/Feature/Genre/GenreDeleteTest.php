<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreDeleteTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーが書籍の紐付いていないジャンルを削除できる
    public function test_authenticated_user_can_delete_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'ジャンル削除',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル削除',
        ]);
    }

    // 未認証ユーザーはジャンルを削除できない
    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ジャンル削除',
        ]);

        $response = $this->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル削除',
        ]);
    }

    // 書籍が紐付いているジャンルは削除できない
    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'ジャンル削除',
        ]);

        $genre->books()->attach($book->id);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            'このジャンルには書籍が紐付いているため削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル削除',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 存在しないジャンルは削除できず404になる
    public function test_nonexistent_genre_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', 9999));

        $response->assertNotFound();
    }
}
