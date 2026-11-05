<?php

namespace Tests\Feature\Navigation;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReportNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 高評価書籍から書籍詳細へ遷移する際、
     * マイレポートを遷移元として保持する。
     */
    public function test_top_rated_book_link_preserves_report_origin(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()
            ->for($user)
            ->for($book)
            ->create(['rating' => 5]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        // 高評価書籍のリンクに遷移元情報が含まれる
        $bookUrl = route('books.show', [
            'book' => $book,
            'from' => 'reports',
        ]);

        $response->assertSee('href="'.$bookUrl.'"', false);

        // 書籍詳細からマイレポートへ戻れる
        $detailResponse = $this->get($bookUrl);

        $detailResponse->assertOk();
        $detailResponse->assertSee(
            'href="'.route('reports.index').'"',
            false
        );
        $detailResponse->assertSee('マイレポートに戻る');
    }

    /**
     * マイレポートから書籍詳細へ遷移し、
     * お気に入り追加後もマイレポートに戻れる。
     */
    public function test_adding_favorite_preserves_report_origin(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // マイレポートからお気に入りを追加
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'reports',
            ]);

        // お気に入りが登録されている
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 遷移元情報を保持して書籍詳細に戻る
        $bookUrl = route('books.show', [
            'book' => $book,
            'from' => 'reports',
        ]);

        $response->assertRedirect($bookUrl);

        // 書籍詳細からマイレポートに戻れる
        $detailResponse = $this->get($bookUrl);

        $detailResponse->assertOk();
        $detailResponse->assertSee(
            'href="'.route('reports.index').'"',
            false
        );
        $detailResponse->assertSee('マイレポートに戻る');
    }

    /**
     * マイレポートから書籍詳細へ遷移し、
     * お気に入り解除後もマイレポートに戻れる。
     */
    public function test_removing_favorite_preserves_report_origin(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 事前にお気に入り登録
        $user->favoriteBooks()->attach($book->id);

        // マイレポートからお気に入りを解除
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book), [
                'from' => 'reports',
            ]);

        // お気に入りが解除されている
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 遷移元情報を保持して書籍詳細に戻る
        $bookUrl = route('books.show', [
            'book' => $book,
            'from' => 'reports',
        ]);

        $response->assertRedirect($bookUrl);

        // 書籍詳細からマイレポートに戻れる
        $detailResponse = $this->get($bookUrl);

        $detailResponse->assertOk();
        $detailResponse->assertSee(
            'href="'.route('reports.index').'"',
            false
        );
        $detailResponse->assertSee('マイレポートに戻る');
    }

    /**
     * マイレポートから書籍詳細へ遷移し、
     * レビューにいいねした後もマイレポートに戻れる。
     */
    public function test_adding_review_like_preserves_report_origin(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // 他ユーザーのレビューを作成
        $review = Review::factory()
            ->for($otherUser)
            ->for($book)
            ->create(['rating' => 5]);

        // マイレポートから遷移した状態でいいねを追加
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review), [
                'from' => 'reports',
            ]);

        // いいねが登録されている
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $bookUrl = route('books.show', [
            'book' => $book,
            'from' => 'reports',
        ]);

        // 遷移元を保持して書籍詳細へ戻る
        $response->assertRedirect($bookUrl);

        // 書籍詳細からマイレポートへ戻れる
        $detailResponse = $this->get($bookUrl);

        $detailResponse->assertOk();
        $detailResponse->assertSee(
            'href="'.route('reports.index').'"',
            false
        );
        $detailResponse->assertSee('マイレポートに戻る');
    }

    /**
     * マイレポートから書籍詳細へ遷移し、
     * レビューのいいね解除後もマイレポートに戻れる。
     */
    public function test_removing_review_like_preserves_report_origin(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // 他ユーザーのレビューを作成
        $review = Review::factory()
            ->for($otherUser)
            ->for($book)
            ->create(['rating' => 5]);

        // 事前にいいねを登録
        $user->likedReviews()->attach($review->id);

        // マイレポートから遷移した状態でいいねを解除
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review), [
                'from' => 'reports',
            ]);

        // いいねが解除されている
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $bookUrl = route('books.show', [
            'book' => $book,
            'from' => 'reports',
        ]);

        // 遷移元を保持して書籍詳細へ戻る
        $response->assertRedirect($bookUrl);

        // 書籍詳細からマイレポートへ戻れる
        $detailResponse = $this->get($bookUrl);

        $detailResponse->assertOk();
        $detailResponse->assertSee(
            'href="'.route('reports.index').'"',
            false
        );
        $detailResponse->assertSee('マイレポートに戻る');
    }

    /**
     * マイレポートから遷移した場合、
     * いいねフォームが遷移元情報を保持する。
     */
    public function test_review_like_form_includes_report_origin(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // マイレポートに表示するため自分のレビューを作成
        Review::factory()
            ->for($user)
            ->for($book)
            ->create(['rating' => 5]);

        // いいね対象は他ユーザーのレビュー
        $review = Review::factory()
            ->for($otherUser)
            ->for($book)
            ->create(['rating' => 4]);

        $response = $this->actingAs($user)
            ->get(route('books.show', [
                'book' => $book,
                'from' => 'reports',
            ]));

        $response->assertOk();

        // 対象レビューのいいねフォームだけを取得
        $action = preg_quote(route('reviews.like', $review), '~');

        $pattern = '~<form\b[^>]*action="'.$action.'"[^>]*>(.*?)</form>~s';

        $this->assertSame(
            1,
            preg_match($pattern, $response->getContent(), $matches)
        );

        // フォームに遷移元が設定されている
        $this->assertStringContainsString(
            'name="from" value="reports"',
            $matches[1]
        );
    }
}
