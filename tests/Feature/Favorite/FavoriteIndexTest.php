<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがお気に入り一覧を表示できる
    public function test_auth_can_view_favorites_list(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee($book->title);
    }

    // 未認証ユーザーはお気に入り一覧画面を表示できない
    public function test_guest_cannot_view_favorites_list(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    // 自分のお気に入り書籍のみ表示される
    public function test_user_can_only_view_own_favorite_books(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $targetBook = Book::factory()->create();
        $otherBook = Book::factory()->create([
            'title' => '他者のお気に入り',
        ]);

        $owner->favoriteBooks()->attach($targetBook->id);
        $otherUser->favoriteBooks()->attach($otherBook->id);

        $response = $this->actingAs($owner)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee($targetBook->title);
        $response->assertDontSee($otherBook->title);
    }

    // 書籍タイトルから詳細画面へ移動できる
    public function test_favorite_book_title_links_to_book_detail(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee(
            'href="'.e(route('books.show', [
                'book' => $book,
                'from' => 'favorites',
                'page' => 1,
            ])).'"',
            false
        );
    }

    // お気に入りが0件の場合、メッセージが表示される
    public function test_message_appears_when_no_favorites_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee('お気に入りに登録された書籍はありません。');
    }

    // お気に入り書籍が1ページ10件ずつ表示される
    public function test_favorite_books_paginate_10_books_by_one_page(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(11)->create();

        $user->favoriteBooks()->attach($books->pluck('id'));

        // 1ページ目
        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });

        // 2ページ目
        $response = $this->actingAs($user)
            ->get(route('favorites.index', ['page' => 2]));

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });
    }

    // お気に入り追加日時が新しい順に表示される
    public function test_favorite_books_are_ordered_by_added_date_desc(): void
    {
        $oldDate = Carbon::parse('2026-10-01 10:00:00');
        $newDate = Carbon::parse('2026-10-05 10:00:00');

        $user = User::factory()->create();
        // 新しいお気に入りにする書籍を先に作成
        $newBook = Book::factory()->create();

        // 古いお気に入りにする書籍を後に作成
        $oldBook = Book::factory()->create();

        $user->favoriteBooks()->attach($oldBook->id, [
            'created_at' => $oldDate,
        ]);

        $user->favoriteBooks()->attach($newBook->id, [
            'created_at' => $newDate,
        ]);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($newBook, $oldBook) {
            return $books->getCollection()->pluck('id')->all() === [
                $newBook->id,
                $oldBook->id,
            ];
        });
    }
}
