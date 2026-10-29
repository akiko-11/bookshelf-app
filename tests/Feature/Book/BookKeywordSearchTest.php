<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookKeywordSearchTest extends TestCase
{
    use RefreshDatabase;

    // タイトルの部分一致検索ができる
    public function test_can_search_books_by_title(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->contains('Laravel入門');
        });
    }

    // 著者名の部分一致検索ができる
    public function test_can_search_books_by_author(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '山田',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === ['PHP入門'];
        });
    }

    // キーワード未入力の場合は全件表示される
    public function test_empty_keyword_returns_all_books(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 2;
        });
    }

    // キーワード「0」でも検索できる
    public function test_can_search_books_with_zero_keyword(): void
    {
        Book::factory()->create([
            'title' => 'PHP 8.0入門',
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $keyword = '0';

        $response = $this->get(route('books.index', [
            'keyword' => $keyword,
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === ['PHP 8.0入門'];
        });
    }
}
