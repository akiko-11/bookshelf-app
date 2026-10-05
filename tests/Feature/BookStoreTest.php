<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    // 正常な入力で書籍を登録できる
    public function test_book_can_be_stored_with_valid_data(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'テストの本',
            'user_id' => $user->id,
        ]);
    }

    // 選択したジャンルが紐付けられる
    public function test_book_is_associated_with_chosen_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        $this->actingAs($user)
            ->post(route('books.store'), $data);

        $book = Book::where('title', 'テストの本')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // 登録成功メッセージが表示される
    public function test_success_message_can_show_when_registering_book(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $book = Book::where('title', 'テストの本')->firstOrFail();

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍を登録しました。');
    }

    // 未認証ユーザーは登録できない
    public function test_guest_cannot_register_book(): void
    {
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $data = $this->validBookData($genre->id);

        $response = $this->post(route('books.store'), $data);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('books', [
            'title' => 'テストの本',
        ]);
    }

    // 正常な書籍登録データを返す
    private function validBookData(int $genreId): array
    {
        return [
            'title' => 'テストの本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-03',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/test.jpg',
            'genres' => [$genreId],
        ];
    }
}
