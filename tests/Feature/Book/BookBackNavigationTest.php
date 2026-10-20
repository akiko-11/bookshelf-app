<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookBackNavigationTest extends TestCase
{
    use RefreshDatabase;

    // 書籍一覧から詳細画面へアクセス元の情報を渡せる
    public function test_can_return_to_original_book_list_page(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', [
            'book' => $book,
            'from' => 'books',
            'page' => 2,
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.route('books.index', ['page' => 2]).'"',
            false
        );
    }

    // お気に入り一覧から詳細画面へアクセス元の情報を渡せる
    public function test_favorites_list_passes_origin_and_page_to_book_detail(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(11)->create();

        $user->favoriteBooks()->attach($books->pluck('id'));

        // お気に入り一覧の2ページ目を表示
        $response = $this->actingAs($user)
            ->get(route('favorites.index', ['page' => 2]));

        $response->assertStatus(200);

        // 2ページ目に表示されている書籍を取得
        $book = $response->viewData('books')->items()[0];

        // 書籍詳細へのリンクにアクセス元とページ番号が含まれること
        $response->assertSee(
            'href="'.e(route('books.show', [
                'book' => $book,
                'from' => 'favorites',
                'page' => 2,
            ])).'"',
            false
        );
    }

    // 書籍一覧から詳細画面へ遷移した場合、元のページに戻れる
    public function test_can_return_to_origin_page_when_moving_from_books_list_to_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', [
            'book' => $book,
            'from' => 'books',
            'page' => 2,
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.route('books.index', ['page' => 2]).'"',
            false
        );
    }

    // お気に入り一覧から詳細画面へ遷移した場合、元のページに戻れる
    public function test_can_return_to_origin_page_when_moving_from_favorites_list_to_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', [
            'book' => $book,
            'from' => 'favorites',
            'page' => 2,
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.route('favorites.index', ['page' => 2]).'"',
            false
        );
    }

    // 書籍一覧から詳細画面へ遷移し、お気に入りを追加しても元のページに戻れる
    public function test_can_return_to_original_books_page_after_adding_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 書籍一覧の2ページ目からお気に入りを追加
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'books',
                'page' => 2,
            ]);

        // お気に入りに登録されたことを確認
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // アクセス元情報を保持して書籍詳細画面へ戻ること
        $response->assertRedirect(route('books.show', [
            'book' => $book,
            'from' => 'books',
            'page' => 2,
        ]));

        // リダイレクト後の詳細画面を表示
        $detailResponse = $this->get(route('books.show', [
            'book' => $book,
            'from' => 'books',
            'page' => 2,
        ]));

        $detailResponse->assertStatus(200);

        // 「一覧に戻る」が書籍一覧の2ページ目を指すこと
        $detailResponse->assertSee(
            'href="'.route('books.index', ['page' => 2]).'"',
            false
        );
    }

    // お気に入り一覧から詳細画面へ遷移し、お気に入り解除後は1ページ目に戻れる
    public function test_can_return_to_first_page_after_removing_from_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        // お気に入り一覧の2ページ目からお気に入りを解除
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'favorites',
                'page' => 2,
            ]);

        // お気に入りが解除されたことを確認
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // アクセス元情報を保持して書籍詳細画面へ戻ること
        $response->assertRedirect(route('books.show', [
            'book' => $book,
            'from' => 'favorites',
            'page' => 1,
        ]));

        // リダイレクト後の詳細画面を表示
        $detailResponse = $this->get(route('books.show', [
            'book' => $book,
            'from' => 'favorites',
            'page' => 1,
        ]));

        $detailResponse->assertStatus(200);

        // 「一覧に戻る」がお気に入り一覧の1ページ目を指すこと
        $detailResponse->assertSee(
            'href="'.route('favorites.index', ['page' => 1]).'"',
            false
        );
    }

    // アクセス元を指定せずに書籍詳細画面を表示した場合、書籍一覧の1ページ目に戻る
    public function test_can_return_to_first_book_list_page_when_no_origin_parameters(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.route('books.index', ['page' => 1]).'"',
            false
        );
    }
}
