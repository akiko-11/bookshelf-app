<?php

namespace Tests\Feature\Navigation;

use App\Models\Book;
use App\Models\Genre;
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

    // ジャンル詳細画面からジャンル一覧に戻るリンクが表示される
    public function test_genre_detail_has_back_link_to_genre_list(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee(
            'href="'.route('genres.index').'"',
            false
        );
    }

    // ジャンル詳細の2ページ目から書籍詳細へ遷移する際、アクセス元情報を渡せる
    public function test_book_link_preserves_genre_and_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        // 2ページ目を作るため11冊登録
        $books = Book::factory()->count(11)->create();
        $genre->books()->attach($books->pluck('id')->all());

        $response = $this->actingAs($user)
            ->get(route('genres.show', [
                'genre' => $genre,
                'page' => 2,
            ]));

        $response->assertOk();

        // 2ページ目に表示された書籍を取得
        $book = $response->viewData('books')->items()[0];

        // 書籍詳細へのリンクにジャンルIDとページ番号が含まれる
        $response->assertSee(
            'href="'.e(route('books.show', [
                'book' => $book,
                'from' => 'genre',
                'genre_id' => $genre->id,
                'page' => 2,
            ])).'"',
            false
        );
    }

    // 書籍詳細から元のジャンル詳細の2ページ目に戻れる
    public function test_book_detail_returns_to_original_genre_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);
        $book = Book::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->get(route('books.show', [
                'book' => $book,
                'from' => 'genre',
                'genre_id' => $genre->id,
                'page' => 2,
            ]));

        $response->assertOk();

        // 戻り先が元のジャンル詳細2ページ目であること
        $response->assertSee(
            'href="'.route('genres.show', [
                'genre' => $genre,
                'page' => 2,
            ]).'"',
            false
        );
    }

    // ジャンル詳細からの遷移後、お気に入り登録してもアクセス元情報を保持する
    public function test_favorite_toggle_preserves_genre_context(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);
        $book = Book::factory()->create();

        $book->genres()->attach($genre->id);

        $params = [
            'book' => $book,
            'from' => 'genre',
            'genre_id' => $genre->id,
            'page' => 2,
        ];

        // お気に入り登録
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'genre',
                'genre_id' => $genre->id,
                'page' => 2,
            ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 遷移元情報を保持して書籍詳細へ戻ること
        $response->assertRedirect(route('books.show', $params));

        // 書籍詳細から元のジャンル詳細へ戻れること
        $detailResponse = $this->get(route('books.show', $params));

        $detailResponse->assertOk();
        $detailResponse->assertSee(
            'href="'.route('genres.show', [
                'genre' => $genre,
                'page' => 2,
            ]).'"',
            false
        );
    }

    // 書籍一覧の2ページ目から詳細へ遷移元情報を渡せる
    public function test_books_list_passes_origin_and_page_to_book_detail(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get(
            route('books.index', ['page' => 2])
        );

        $response->assertOk();

        // 2ページ目に表示されている書籍を取得
        $book = $response->viewData('books')->items()[0];

        // 書籍詳細へのリンクにアクセス元とページ番号が含まれること
        $response->assertSee(
            'href="'.e(route('books.show', [
                'book' => $book,
                'from' => 'books',
                'page' => 2,
            ])).'"',
            false
        );
    }

    // 検索条件付きの書籍一覧から詳細画面へ遷移し、元の検索結果に戻れる
    public function test_book_detail_preserves_search_conditions_when_returning_to_list(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', [
            'book' => $book,
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.e(route('books.index', [
                'page' => 2,
                'keyword' => 'Laravel',
                'sort' => 'oldest',
            ])).'"',
            false
        );
    }

    // お気に入り追加後も検索条件とページ番号が保持される
    public function test_adding_favorite_preserves_search_conditions(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 書籍一覧の2ページ目からお気に入りを追加
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'books',
                'page' => 2,
                'keyword' => 'Laravel',
                'sort' => 'oldest',
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
            'keyword' => 'Laravel',
            'sort' => 'oldest',
        ]));
    }

    // お気に入り解除後も検索条件とページ番号が保持される
    public function test_removing_favorite_preserves_search_conditions(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        // 検索条件付きの書籍一覧の2ページ目からお気に入りを解除
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'books',
                'page' => 2,
                'keyword' => 'Laravel',
                'sort' => 'oldest',
            ]);

        // お気に入りが解除されたことを確認
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // アクセス元情報を保持して書籍詳細画面へ戻ること
        $response->assertRedirect(route('books.show', [
            'book' => $book,
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'sort' => 'oldest',
        ]));
    }

    // 検索条件付きの書籍一覧から詳細画面へ検索条件とページ番号を渡せる
    public function test_books_list_passes_search_conditions_to_book_detail(): void
    {
        $books = Book::factory()->count(11)->create([
            'title' => 'Laravel',
        ]);
        $genre = Genre::factory()->create();

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->get(
            route('books.index', [
                'page' => 2,
                'keyword' => 'Laravel',
                'genre' => $genre->id,
                'sort' => 'oldest',
            ])
        );

        $response->assertOk();

        // 2ページ目に表示されている書籍を取得
        $listedBook = $response->viewData('books')->items()[0];

        $response->assertSee(
            'href="'.e(route('books.show', [
                'book' => $listedBook,
                'from' => 'books',
                'page' => 2,
                'keyword' => 'Laravel',
                'genre' => $genre->id,
                'sort' => 'oldest',
            ])).'"',
            false
        );
    }
}
