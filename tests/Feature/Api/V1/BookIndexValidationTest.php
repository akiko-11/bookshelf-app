<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexValidationTest extends TestCase
{
    use RefreshDatabase;

    // キーワードが文字列以外の場合はエラーになる
    public function test_rejects_non_string_keyword(): void
    {
        $response = $this->getJson('/api/v1/books?keyword[]=Laravel');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('keyword');
        $response->assertJsonPath(
            'errors.keyword.0',
            'キーワードは文字列で入力してください'
        );
    }

    // キーワードが255文字を超える場合はエラーになる
    public function test_rejects_keyword_over_255_characters(): void
    {
        $keyword = str_repeat('a', 256);

        $response = $this->getJson("/api/v1/books?keyword={$keyword}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('keyword');
        $response->assertJsonPath(
            'errors.keyword.0',
            'キーワードは255文字以内で入力してください'
        );
    }

    // キーワードが255文字の場合は正常に取得できる
    public function test_accepts_keyword_with_255_characters(): void
    {
        $keyword = str_repeat('a', 255);

        $response = $this->getJson("/api/v1/books?keyword={$keyword}");

        $response->assertOk();
    }

    // ジャンルIDが整数以外の場合はエラーになる
    public function test_rejects_non_integer_genre_id(): void
    {
        $genreId = 'abc';

        $response = $this->getJson("/api/v1/books?genre_id={$genreId}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('genre_id');
        $response->assertJsonPath(
            'errors.genre_id.0',
            'ジャンルIDは整数で入力してください'
        );
    }

    // 存在しないジャンルIDの場合はエラーになる
    public function test_rejects_nonexistent_genre_id(): void
    {
        $genreId = 99999;

        $response = $this->getJson("/api/v1/books?genre_id={$genreId}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('genre_id');
        $response->assertJsonPath(
            'errors.genre_id.0',
            '指定されたジャンルが存在しません'
        );
    }

    // ページが整数以外の場合はエラーになる
    public function test_rejects_non_integer_page(): void
    {
        $page = 'abc';

        $response = $this->getJson("/api/v1/books?page={$page}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('page');
        $response->assertJsonPath(
            'errors.page.0',
            'ページ番号は整数で入力してください'
        );
    }

    // ページが1未満の場合はエラーになる
    public function test_rejects_page_less_than_one(): void
    {
        $page = 0;

        $response = $this->getJson("/api/v1/books?page={$page}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('page');
        $response->assertJsonPath(
            'errors.page.0',
            'ページ番号は1以上で指定してください'
        );
    }

    // 1ページあたりの件数が整数以外の場合はエラーになる
    public function test_rejects_non_integer_per_page(): void
    {
        $perPage = 'abc';

        $response = $this->getJson("/api/v1/books?per_page={$perPage}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('per_page');
        $response->assertJsonPath(
            'errors.per_page.0',
            '1ページあたりの件数は整数で入力してください'
        );
    }

    // 1ページあたりの件数が1未満の場合はエラーになる
    public function test_rejects_per_page_less_than_one(): void
    {
        $perPage = 0;

        $response = $this->getJson("/api/v1/books?per_page={$perPage}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('per_page');
        $response->assertJsonPath(
            'errors.per_page.0',
            '1ページあたりの件数は1件以上で指定してください'
        );
    }

    // 1ページあたりの件数が100超過の場合はエラーになる
    public function test_rejects_per_page_over_100(): void
    {
        $perPage = 101;

        $response = $this->getJson("/api/v1/books?per_page={$perPage}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('per_page');
        $response->assertJsonPath(
            'errors.per_page.0',
            '1ページあたりの件数は100件以内で指定してください'
        );
    }

    // 1ページあたりの件数が1の場合は正常に取得できる
    public function test_accepts_per_page_of_1(): void
    {
        $perPage = 1;

        $response = $this->getJson("/api/v1/books?per_page={$perPage}");

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 1);
    }

    // 1ページあたりの件数が100の場合は正常に取得できる
    public function test_accepts_per_page_of_100(): void
    {
        $perPage = 100;

        $response = $this->getJson("/api/v1/books?per_page={$perPage}");

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 100);
    }

    // パラメータ未指定の場合は初期値が適用される
    public function test_uses_default_values_when_parameters_are_omitted(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 20);
    }
}
