<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
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

    // 書籍が作成日時の新しい順に表示される
    public function test_books_are_ordered_by_created_at_desc(): void
    {
        $newest = Book::factory()->create([
            'created_at' => now(),
        ]);

        $oldest = Book::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $middle = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            $response->viewData('books')->pluck('id')->all()
        );
    }
}
