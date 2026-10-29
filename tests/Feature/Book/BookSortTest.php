<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSortTest extends TestCase
{
    use RefreshDatabase;

    // newestを指定すると、書籍が登録日時の新しい順に表示される
    public function test_books_are_sorted_by_newest(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now()->subDays(3),
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDays(1),
        ]);

        Book::factory()->create([
            'title' => 'Java入門',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Java入門',
                'Laravel入門',
                'PHP入門',
            ];
        });
    }

    // oldestを指定すると、書籍が登録日時の古い順に表示される
    public function test_books_are_sorted_by_oldest(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now()->subDays(3),
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDays(1),
        ]);

        Book::factory()->create([
            'title' => 'Java入門',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'PHP入門',
                'Laravel入門',
                'Java入門',
            ];
        });
    }

    // タイトルの昇順に書籍が表示される
    public function test_books_are_sorted_by_title(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now(),
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => 'Java入門',
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Java入門',
                'Laravel入門',
                'PHP入門',
            ];
        });
    }

    // レビューの平均評価が高い順に書籍が表示される
    public function test_books_are_sorted_by_rating(): void
    {
        $bookA = Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now(),
        ]);

        $bookB = Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDay(),
        ]);

        $bookC = Book::factory()->create([
            'title' => 'Java入門',
            'created_at' => now()->subDays(3),
        ]);

        foreach ([3, 5] as $rating) {
            Review::factory()->create([
                'book_id' => $bookA->id,
                'rating' => $rating,
            ]);
        }

        foreach ([5, 5] as $rating) {
            Review::factory()->create([
                'book_id' => $bookB->id,
                'rating' => $rating,
            ]);
        }

        foreach ([2, 4] as $rating) {
            Review::factory()->create([
                'book_id' => $bookC->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Laravel入門',
                'PHP入門',
                'Java入門',
            ];
        });
    }

    // レビューがない書籍は評価順の最後に表示される
    public function test_books_without_reviews_are_sorted_last(): void
    {
        $bookA = Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now(),
        ]);

        $bookB = Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDay(),
        ]);

        $bookC = Book::factory()->create([
            'title' => 'Java入門',
            'created_at' => now()->subDays(3),
        ]);

        foreach ([3, 3] as $rating) {
            Review::factory()->create([
                'book_id' => $bookB->id,
                'rating' => $rating,
            ]);
        }

        foreach ([5, 5] as $rating) {
            Review::factory()->create([
                'book_id' => $bookC->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Java入門',
                'Laravel入門',
                'PHP入門',
            ];
        });
    }

    // キーワード検索・ジャンル絞り込み・並び替えを併用できる
    public function test_sort_can_be_combined_with_filters(): void
    {
        $bookA = Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now(),
        ]);

        $bookB = Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDays(3),
        ]);

        $bookC = Book::factory()->create([
            'title' => 'Laravel実践',
            'created_at' => now()->subDays(1),
        ]);

        $bookD = Book::factory()->create([
            'title' => 'Laravel小説',
            'created_at' => now()->subDays(2),
        ]);

        $novel = Genre::create(['name' => '小説']);

        $techGenre = Genre::create(['name' => '技術書']);

        $bookA->genres()->attach($techGenre->id);
        $bookB->genres()->attach($techGenre->id);
        $bookC->genres()->attach($techGenre->id);
        $bookD->genres()->attach($novel->id);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $techGenre->id,
            'sort' => 'oldest',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Laravel入門',
                'Laravel実践',
            ];
        });
    }
}
