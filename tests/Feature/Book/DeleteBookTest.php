<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteBookTest extends TestCase
{
    use RefreshDatabase;

    // 登録者本人が書籍を削除できる
    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    // 削除後に書籍一覧へ遷移し、FlashMessageが表示される
    public function test_deleting_book_redirects_to_index_with_success_message(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );
    }

    // 他ユーザーは削除できず403になる
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 未ログインでは削除できずログイン画面に遷移する
    public function test_guest_cannot_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->delete("/books/{$book->id}");

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 書籍削除時に関連データも削除される
    public function test_deleting_book_removes_related_data(): void
    {
        $user = User::factory()->create();
        $favoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $book->genres()->attach($genre->id);

        $favoriteUser->favoriteBooks()->attach($book->id);

        DB::table('review_likes')->insert([
            'user_id' => $favoriteUser->id,
            'review_id' => $review->id,
        ]);

        // いいねが登録されていることを確認
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $favoriteUser->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        // ジャンル自体は削除されない
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        // 書籍とジャンルの紐付けが削除されている
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $favoriteUser->id,
            'review_id' => $review->id,
        ]);
    }
}
