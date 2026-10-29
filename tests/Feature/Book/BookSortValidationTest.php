<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSortValidationTest extends TestCase
{
    use RefreshDatabase;

    // 許可された4種類のソート値は有効である
    public function test_valid_sort_values_are_accepted(): void
    {
        $sorts = ['newest', 'oldest', 'title', 'rating'];

        foreach ($sorts as $sort) {
            $response = $this->get(route('books.index', [
                'sort' => $sort,
            ]));
            $response->assertOK();
        }
    }

    // 定義されていないソート値はバリデーションエラーになる
    public function test_invalid_sort_value_is_rejected(): void
    {
        $sort = 'invalid';

        $response = $this->get(route('books.index', [
            'sort' => $sort,
        ]));

        $response->assertSessionHasErrors([
            'sort' => '指定された並び順は無効です',
        ]);
    }

    // ソート未指定の場合は新しい順に表示される
    public function test_missing_sort_defaults_to_newest(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now()->subDays(3),
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Laravel入門',
                'PHP入門',
            ];
        });
    }

    // ソートが空欄の場合は新しい順に表示される
    public function test_empty_sort_defaults_to_newest(): void
    {
        Book::factory()->create([
            'title' => 'PHP入門',
            'created_at' => now()->subDays(3),
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => '',
        ]));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->pluck('title')->all() === [
                'Laravel入門',
                'PHP入門',
            ];
        });
    }

    // 文字列以外のソート値はバリデーションエラーになる
    public function test_non_string_sort_is_invalid(): void
    {
        $sort = ['newest'];

        $response = $this->get(route('books.index', [
            'sort' => $sort,
        ]));

        $response->assertSessionHasErrors([
            'sort' => '指定された並び順は無効です',
        ]);
    }
}
