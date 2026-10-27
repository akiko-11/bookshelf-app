<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    // お気に入り登録したユーザーを取得できる
    public function test_favorite_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $favoriteUser = $favorite->user;

        $this->assertTrue($favoriteUser->is($user));
    }

    // お気に入り登録された書籍を取得できる
    public function test_favorite_belongs_to_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $favoriteBook = $favorite->book;

        $this->assertTrue($favoriteBook->is($book));
    }
}
