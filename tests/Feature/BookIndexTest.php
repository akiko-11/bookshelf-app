<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍一覧画面を表示できる
    public function test_guest_can_view_book_index(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    // 書籍が10件/ページで表示される
    public function test_books_are_paginated_by_10(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11;
        });
    }

    // 書籍にジャンルが表示される
    public function test_books_show_genre(): void
    {
        $book = Book::factory()->create([
            'title' => 'テストの本',
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->sync([$genre->id]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('テストジャンル');
    }

    // 書籍詳細画面を表示できる
    public function test_books_detail_show(): void
    {
        $book = Book::factory()->create([
            'title' => 'テストの本',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テストの本');
    }

    // 未認証ユーザーは書籍登録画面へアクセスするとログイン画面へリダイレクトされる
    public function test_guest_is_redirected_to_login_when_accessing_book_create(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    // 認証ユーザーは書籍登録画面へアクセスできる
    public function test_authenticated_user_can_access_book_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('books.create'));

        $response->assertStatus(200);
    }
}
