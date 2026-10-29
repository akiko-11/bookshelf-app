<?php

namespace Tests\Feature\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookGenreFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    // 整数以外のジャンルIDはバリデーションエラーになる
    public function test_non_integer_genre_is_invalid(): void
    {
        $genre = 'abc';

        $response = $this->get(route('books.index', [
            'genre' => $genre,
        ]));

        $response->assertSessionHasErrors([
            'genre' => 'ジャンルIDは整数で指定してください',
        ]);
    }

    // 存在しないジャンルIDはエラーになる
    public function test_nonexistent_genre_is_invalid(): void
    {
        $genre = 99999;

        $response = $this->get(route('books.index', [
            'genre' => $genre,
        ]));

        $response->assertSessionHasErrors([
            'genre' => '指定されたジャンルが存在しません',
        ]);
    }

    // ジャンルが空欄の場合はエラーにならない
    public function test_empty_genre_is_valid(): void
    {
        $response = $this->get(route('books.index', [
            'genre' => '',
        ]));

        $response->assertOk();
    }
}
