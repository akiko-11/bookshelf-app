<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがジャンル詳細画面を表示できる
    public function test_authenticated_user_can_view_genre_detail(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertStatus(200);
    }

    // 未認証ユーザーはログイン画面へリダイレクトされる
    public function test_guest_cannot_view_genre_detail(): void
    {
        $genre = Genre::factory()->create();

        $response = $this
            ->get(route('genres.show', $genre));

        $response->assertRedirect(route('login'));
    }

    // 指定したジャンル名が表示される
    public function test_genre_detail_displays_genre_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee($genre->name);
    }

    // ジャンルに紐づく書籍が表示される
    public function test_genre_detail_displays_associated_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee($book->title);
    }

    // 他のジャンルにのみ紐づく書籍は表示されない
    public function test_genre_detail_does_not_display_unrelated_books(): void
    {
        $user = User::factory()->create();
        $targetGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $targetBook = Book::factory()->create([
            'title' => '対象ジャンルの書籍',
        ]);
        $otherBook = Book::factory()->create([
            'title' => '別ジャンルの書籍',
        ]);

        $targetGenre->books()->attach($targetBook->id);
        $otherGenre->books()->attach($otherBook->id);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $targetGenre));

        $response->assertStatus(200);
        $response->assertSee($targetBook->title);
        $response->assertDontSee($otherBook->title);
    }

    // 書籍が10件ずつページネーションされる
    public function test_genre_books_are_paginated_ten_per_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create();

        $genre->books()->attach($books->pluck('id'));

        // 1ページ目
        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });

        // 2ページ目
        $response = $this->get(route('genres.show', [
            'genre' => $genre,
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });
    }

    // 紐づく書籍がない場合、メッセージが表示される
    public function test_genre_detail_displays_message_when_no_books_exist(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }

    // 存在しないジャンルIDへのアクセスは404になる
    public function test_nonexistent_genre_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('genres.show', 999999));

        $response->assertStatus(404);
    }

    // ジャンル一覧から詳細画面へのリンクが表示される
    public function test_genre_list_links_to_genre_detail(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('genres.index'));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.route('genres.show', $genre).'"',
            false
        );
    }
}
