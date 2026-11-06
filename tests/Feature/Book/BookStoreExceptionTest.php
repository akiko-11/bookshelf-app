<?php

namespace Tests\Feature\Book;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class BookStoreExceptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ジャンル紐付け時にDB例外が発生した場合、
     * 書籍登録がロールバックされ、
     * 登録画面にエラーメッセージが表示される。
     */
    public function test_book_registration_rolls_back_when_genre_sync_fails(): void
    {
        // ユーザーとジャンルを準備
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        // book_genreへのINSERT成功直後にDB例外を発生させる
        DB::listen(function (QueryExecuted $query): void {
            if (preg_match(
                '/insert\s+into\s+[`"]?book_genre[`"]?/i',
                $query->sql
            )) {
                throw new QueryException(
                    $query->connectionName,
                    $query->sql,
                    $query->bindings,
                    new PDOException('ジャンル登録時のテスト用DB例外')
                );
            }
        });

        // 書籍登録リクエスト
        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $data);

        // 登録画面に戻る
        $response->assertRedirect(route('books.create'));

        // 失敗メッセージがセッションに保存される
        $response->assertSessionHas(
            'error',
            '書籍の登録に失敗しました。'
        );

        // 入力内容が保持される
        $response->assertSessionHas(
            '_old_input.title',
            $data['title']
        );

        $response->assertSessionHas(
            '_old_input.genres',
            $data['genres']
        );

        // 書籍INSERT後の例外によって登録内容がロールバックされる
        $this->assertDatabaseCount('books', 0);

        // 中間テーブルにも不完全なデータが残らない
        $this->assertDatabaseCount('book_genre', 0);

        // 登録画面を再表示する
        $page = $this->get(route('books.create'));

        $page->assertOk();

        // 画面上に失敗メッセージが表示される
        $page->assertSeeText('書籍の登録に失敗しました。');

        // タイトルの入力値が復元される
        $page->assertSee('value="テストの本"', false);
    }

    /**
     * 書籍登録用の正常なリクエストデータを作成する。
     */
    private function validBookData(int $genreId): array
    {
        return [
            'title' => 'テストの本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/test.jpg',
            'genres' => [$genreId],
        ];
    }
}
