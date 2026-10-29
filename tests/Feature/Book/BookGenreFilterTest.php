<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookGenreFilterTest extends TestCase
{
    use RefreshDatabase;

    // 指定したジャンルに属する書籍のみ表示される
    public function test_books_can_be_filtered_by_genre(): void
    {
        $book = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $novel = Genre::create(['name' => '小説']);

        $techGenre = Genre::create(['name' => '技術書']);

        $book->genres()->attach($novel->id);
        $otherBook->genres()->attach($techGenre->id);

        $response = $this->get(route('books.index', [
            'genre' => $novel->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === ['吾輩は猫である'];
        });
    }

    // キーワード検索とジャンル絞り込みを併用できる
    public function test_can_filter_books_by_keyword_and_genre(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $otherBookA = Book::factory()->create([
            'title' => 'Laravel実践',
        ]);

        $otherBookB = Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        $novel = Genre::create(['name' => '小説']);

        $techGenre = Genre::create(['name' => '技術書']);

        $book->genres()->attach($techGenre->id);
        $otherBookA->genres()->attach($novel->id);
        $otherBookB->genres()->attach($techGenre->id);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $techGenre->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === ['Laravel入門'];
        });
    }

    // ジャンルが空欄の場合はすべての書籍が表示される
    public function test_empty_genre_returns_all_books(): void
    {
        $book = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $novel = Genre::create(['name' => '小説']);

        $techGenre = Genre::create(['name' => '技術書']);

        $book->genres()->attach($novel->id);
        $otherBook->genres()->attach($techGenre->id);

        $response = $this->get(route('books.index', [
            'genre' => '',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 2;
        });
    }
}
