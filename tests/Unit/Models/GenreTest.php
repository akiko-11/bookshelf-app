<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    // ジャンルに紐づく書籍を取得できる
    public function test_genre_has_books(): void
    {
        $user = $this->createUser();
        $book = $this->createBook($user);

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book->genres()->attach($genre->id);

        $books = $genre->books;

        $this->assertTrue($books->contains($book));
    }

    // ユーザー作成
    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'テストユーザー',
        ]);
    }

    // 書籍データ作成
    private function createBook(User $user): Book
    {
        return Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'テスト用の説明です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);
    }
}
