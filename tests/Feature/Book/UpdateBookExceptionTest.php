<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateBookExceptionTest extends TestCase
{
    use RefreshDatabase;

    // DB更新に失敗した場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_book_update_fails(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        // Book更新時にテスト用の例外を発生させる
        Book::updated(function () {
            throw new \RuntimeException('テスト用例外');
        });

        $response = $this->actingAs($user)
            ->from(route('books.edit', $book))
            ->put(
                route('books.update', $book),
                $data
            );

        $response->assertRedirect(route('books.edit', $book));

        $response->assertSessionHas(
            'error',
            '書籍の更新に失敗しました。'
        );
    }

    // DB更新に失敗した場合、書籍・ジャンルの更新がロールバックされる
    public function test_book_and_genres_are_rolled_back_when_update_fails(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
        ]);

        // 更新前ジャンルを作成して書籍に紐付ける
        $oldGenre = Genre::create([
            'name' => '更新前ジャンル',
        ]);

        $book->genres()->attach($oldGenre->id);

        // 更新後に指定するジャンル
        $newGenre = Genre::create([
            'name' => '更新後ジャンル',
        ]);

        $data = $this->validBookData($newGenre->id, [
            'title' => '更新後タイトル',
        ]);

        // 書籍更新時に意図的に例外を発生させる
        Book::updated(function () {
            throw new \RuntimeException('テスト用例外');
        });

        $response = $this->actingAs($user)
            ->from(route('books.edit', $book))
            ->put(
                route('books.update', $book),
                $data
            );

        // 編集画面へ戻る
        $response->assertRedirect(route('books.edit', $book));

        // エラーメッセージ
        $response->assertSessionHas(
            'error',
            '書籍の更新に失敗しました。'
        );

        // 書籍情報が更新前のまま
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);

        // 更新後のタイトルにはなっていない
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);

        $book->refresh();

        // 更新前ジャンルが残っている
        $this->assertTrue(
            $book->genres->contains($oldGenre->id)
        );

        // 更新後ジャンルは紐付いていない
        $this->assertFalse(
            $book->genres->contains($newGenre->id)
        );
    }

    // ジャンル同期中に失敗した場合、書籍とジャンルの変更がロールバックされる
    public function test_book_and_genres_are_rolled_back_when_genre_sync_fails(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
        ]);

        // 更新前ジャンルを作成して書籍に紐付ける
        $oldGenre = Genre::create([
            'name' => '更新前ジャンル',
        ]);

        $book->genres()->attach($oldGenre->id);

        // 更新後に指定するジャンル
        $newGenre = Genre::create([
            'name' => '更新後ジャンル',
        ]);

        $data = $this->validBookData($newGenre->id, [
            'title' => '更新後タイトル',
        ]);

        // ジャンルの中間テーブルへのINSERT後に例外を発生させる
        DB::listen(function (QueryExecuted $query): void {
            if (preg_match('/insert\s+into\s+[`"]?book_genre[`"]?/i', $query->sql)) {
                throw new \RuntimeException('ジャンル同期中のテスト用例外');
            }
        });

        $response = $this->actingAs($user)
            ->from(route('books.edit', $book))
            ->put(
                route('books.update', $book),
                $data
            );

        // 編集画面へ戻る
        $response->assertRedirect(route('books.edit', $book));

        // エラーメッセージ
        $response->assertSessionHas(
            'error',
            '書籍の更新に失敗しました。'
        );

        // 書籍情報が更新前のまま
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);

        // 更新後のタイトルにはなっていない
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);

        $book->refresh();

        // 更新前ジャンルが残っている
        $this->assertTrue(
            $book->genres->contains($oldGenre->id)
        );

        // 更新後ジャンルは紐付いていない
        $this->assertFalse(
            $book->genres->contains($newGenre->id)
        );
    }

    // 書籍更新用のデータ
    private function validBookData(int $genreId, array $overrides = []): array
    {
        return array_merge([
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => '更新後説明',
            'image_url' => 'https://example.com/test.jpg',
            'genres' => [$genreId],
        ], $overrides);
    }
}
