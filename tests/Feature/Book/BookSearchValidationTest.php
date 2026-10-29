<?php

namespace Tests\Feature\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchValidationTest extends TestCase
{
    use RefreshDatabase;

    // ページ番号が整数でない場合はバリデーションエラーになる
    public function test_page_must_be_integer(): void
    {
        $page = 'abc';

        $response = $this->get(route('books.index', [
            'page' => $page,
        ]));

        $response->assertSessionHasErrors([
            'page' => 'ページ番号は整数で指定してください',
        ]);
    }

    // ページ番号が0の場合はバリデーションエラーになる
    public function test_page_must_be_at_least_one(): void
    {
        $page = 0;

        $response = $this->get(route('books.index', [
            'page' => $page,
        ]));

        $response->assertSessionHasErrors([
            'page' => 'ページ番号は1以上で指定してください',
        ]);
    }

    // ページ番号が-1の場合はバリデーションエラーになる
    public function test_page_cannot_be_negative(): void
    {
        $page = -1;

        $response = $this->get(route('books.index', [
            'page' => $page,
        ]));

        $response->assertSessionHasErrors([
            'page' => 'ページ番号は1以上で指定してください',
        ]);
    }
}
