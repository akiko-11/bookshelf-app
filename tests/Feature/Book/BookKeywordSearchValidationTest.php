<?php

namespace Tests\Feature\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookKeywordSearchValidationTest extends TestCase
{
    use RefreshDatabase;

    // 255文字以内のキーワードは許可される
    public function test_keyword_within_255_characters_is_valid(): void
    {
        $keyword = str_repeat('a', 255);

        $response = $this->get(route('books.index', [
            'keyword' => $keyword,
        ]));

        $response->assertOk();
    }

    // 255文字を超えるキーワードはバリデーションエラーになる
    public function test_keyword_exceeding_255_characters_is_invalid(): void
    {
        $keyword = str_repeat('a', 256);

        $response = $this->get(route('books.index', [
            'keyword' => $keyword,
        ]));

        $response->assertSessionHasErrors([
            'keyword' => 'キーワードは255文字以内で入力してください',
        ]);
    }

    // 文字列以外のキーワードはバリデーションエラーになる
    public function test_non_string_keyword_is_invalid(): void
    {
        $keyword = ['Laravel'];

        $response = $this->get(route('books.index', [
            'keyword' => $keyword,
        ]));

        $response->assertSessionHasErrors([
            'keyword' => 'キーワードは文字列で入力してください',
        ]);
    }
}
