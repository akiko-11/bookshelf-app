<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditBookTest extends TestCase
{
    use RefreshDatabase;

    // 作成者本人は書籍編集画面を表示できる
    public function test_owner_can_view_book_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('books.edit', $book),
        );

        $response->assertStatus(200);
    }

    // 既存の書籍情報が初期値として表示される
    public function test_existing_book_data_is_displayed_on_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('books.edit', $book),
        );

        $response->assertSee($book->title);
        $response->assertSee($book->author);
        $response->assertSee($book->isbn);
        $response->assertSee($book->published_date->format('Y-m-d'));
        $response->assertSee($book->description);
        $response->assertSee($book->image_url);
    }

    // 現在紐付いているジャンルが選択済みで表示される
    public function test_existing_genre_is_checked_on_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->get(
            route('books.edit', $book),
        );

        $response->assertStatus(200);

        $response->assertSee('テストジャンル');
        $response->assertSee('checked', false);
    }

    // 他ユーザーは書籍編集画面を表示できない
    public function test_other_user_cannot_view_book_edit_page(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $targetUser->id,
        ]);

        $response = $this->actingAs($otherUser)->get(
            route('books.edit', $book),
        );

        $response->assertForbidden();
    }

    // 未認証ユーザーは書籍編集画面を表示できない
    public function test_guest_cannot_view_book_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get(
            route('books.edit', $book),
        );

        $response->assertRedirect(route('login'));
    }
}
