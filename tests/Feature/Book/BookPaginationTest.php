<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPaginationTest extends TestCase
{
    use RefreshDatabase;

    // 2ページ目に移動しても並び順の条件が保持される
    public function test_pagination_preserves_sort_condition(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            route('books.index', [
                'sort' => 'oldest',
                'page' => 2,
            ])
        );
    }

    // 2ページ目に移動してもキーワード検索条件が保持される
    public function test_pagination_preserves_keyword_condition(): void
    {
        Book::factory()->count(11)->create([
            'title' => 'Laravel',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            route('books.index', [
                'keyword' => 'Laravel',
                'page' => 2,
            ])
        );
    }

    // 2ページ目に移動してもジャンル絞り込み条件が保持される
    public function test_pagination_preserves_genre_condition(): void
    {
        $books = Book::factory()->count(11)->create();
        $genre = Genre::factory()->create();

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->get(route('books.index', [
            'genre' => $genre->id,
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            route('books.index', [
                'genre' => $genre->id,
                'page' => 2,
            ])
        );
    }

    // 複数の検索条件を指定しても2ページ目へのリンクにすべて保持される
    public function test_pagination_preserves_combined_search_conditions(): void
    {
        $books = Book::factory()->count(11)->create([
            'title' => 'Laravel',
        ]);
        $genre = Genre::factory()->create();

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            route('books.index', [
                'keyword' => 'Laravel',
                'genre' => $genre->id,
                'sort' => 'oldest',
                'page' => 2,
            ])
        );
    }

    // 複数の検索条件を指定した2ページ目に残りの書籍が表示される
    public function test_second_page_displays_remaining_filtered_books(): void
    {
        $books = Book::factory()->count(11)->create([
            'title' => 'Laravel',
        ]);
        $genre = Genre::factory()->create();

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'oldest',
            'page' => 2,
        ]));

        $response->assertStatus(200);

        $response->assertViewHas('books', function ($books) {
            return $books->currentPage() === 2
                && $books->count() === 1;
        });
    }

    // ページ番号が未指定の場合は1ページ目が表示される
    public function test_displays_first_page_when_page_is_not_specified(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get(route('books.index'));

        $response->assertStatus(200);

        $response->assertViewHas('books', function ($books) {
            return $books->currentPage() === 1;
        });
    }
}
