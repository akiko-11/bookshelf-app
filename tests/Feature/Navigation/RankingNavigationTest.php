<?php

namespace Tests\Feature\Navigation;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 評価ランキングから書籍詳細へ遷移し、
     * ランキング一覧へ戻れる。
     */
    public function test_ranking_book_link_preserves_origin(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // ランキングに表示するためレビューを作成
        Review::factory()
            ->for($user)
            ->for($book)
            ->create(['rating' => 5]);

        // 評価ランキングを表示
        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        // ランキングから書籍詳細へのリンクを確認
        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'ranking',
        ]);

        $response->assertSee(
            'href="'.e($detailUrl).'"',
            false
        );

        // 書籍詳細を表示
        $response = $this->get($detailUrl);

        $response->assertOk();

        // ランキングに戻るリンクが表示される
        $response->assertSee(
            'href="'.route('ranking.index').'"',
            false
        );
    }

    /**
     * 評価ランキングから書籍詳細へ遷移し、
     * お気に入り追加後もランキングへ戻れる。
     */
    public function test_adding_favorite_preserves_ranking_origin(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // ランキングに表示するためレビューを作成
        Review::factory()
            ->for($user)
            ->for($book)
            ->create(['rating' => 5]);

        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'ranking',
        ]);

        // ランキングから遷移した書籍詳細を表示
        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertOk();

        // お気に入りフォームに遷移元が保持される
        $response->assertSee(
            'name="from" value="ranking"',
            false
        );

        // お気に入りを追加
        $response = $this->post(
            route('favorites.toggle', $book),
            [
                'from' => 'ranking',
            ]
        );

        // お気に入りが登録されている
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // ランキングの遷移元情報を保持して書籍詳細へ戻る
        $response->assertRedirect($detailUrl);

        // 書籍詳細からランキングへ戻れる
        $response = $this->get($detailUrl);

        $response->assertOk();

        $response->assertSee(
            'href="'.route('ranking.index').'"',
            false
        );
    }

    /**
     * 評価ランキングから書籍詳細へ遷移し、
     * レビューのいいね解除後もランキングへ戻れる。
     */
    public function test_removing_review_like_preserves_ranking_origin(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = Book::factory()->create();

        // ランキングに表示するため他ユーザーのレビューを作成
        $review = Review::factory()
            ->for($reviewer)
            ->for($book)
            ->create(['rating' => 5]);

        // あらかじめいいねを登録
        $user->likedReviews()->attach($review->id);

        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'ranking',
        ]);

        // いいね解除前の書籍詳細を表示
        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertOk();

        // 対象レビューのいいね解除フォームを取得
        $action = preg_quote(route('reviews.like', $review), '~');
        $pattern = '~<form\b[^>]*action="'.$action.'"[^>]*>(.*?)</form>~s';

        $this->assertSame(
            1,
            preg_match($pattern, $response->getContent(), $matches)
        );

        // 解除フォームに遷移元が保持される
        $this->assertStringContainsString(
            'name="from" value="ranking"',
            $matches[1]
        );

        // レビューのいいねを解除
        $response = $this->post(
            route('reviews.like', $review),
            [
                'from' => 'ranking',
            ]
        );

        // いいねが削除されている
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // ランキングの遷移元情報を保持して書籍詳細へ戻る
        $response->assertRedirect($detailUrl);

        // 書籍詳細からランキングへ戻れる
        $response = $this->get($detailUrl);

        $response->assertOk();

        $response->assertSee(
            'href="'.route('ranking.index').'"',
            false
        );
    }
}
