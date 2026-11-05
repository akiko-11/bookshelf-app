<?php

namespace Tests\Feature\Navigation;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 読書計画一覧から開いた書籍詳細画面では、
     * 元の状態フィルターを保持した一覧へのリンクが表示される。
     */
    public function test_book_detail_back_link_preserves_reading_plan_status(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 読書計画一覧から書籍詳細へ移動した状態を再現
        $response = $this->actingAs($user)->get(
            route('books.show', [
                'book' => $book,
                'from' => 'reading-plans',
                'status' => 'in_progress',
            ])
        );

        $response->assertOk();

        // 戻り先が「進行中」の読書計画一覧であることを確認
        $expectedUrl = route('reading-plans.index', [
            'status' => 'in_progress',
        ]);

        $response->assertSee(
            'href="'.e($expectedUrl).'"',
            false
        );
    }

    /**
     * 読書計画一覧から開いた書籍をお気に入り登録しても、
     * 遷移元と状態フィルターが保持される。
     */
    public function test_adding_favorite_preserves_reading_plan_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 読書計画一覧から開いた書籍詳細を表示
        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'reading-plans',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertOk();

        // お気に入りフォームに状態フィルターが保持される
        $response->assertSee(
            'name="status" value="in_progress"',
            false
        );

        // お気に入りに追加
        $response = $this->actingAs($user)->post(
            route('favorites.toggle', $book),
            [
                'from' => 'reading-plans',
                'status' => 'in_progress',
            ]
        );

        // 遷移元とフィルターを保持した書籍詳細へ戻る
        $response->assertRedirect($detailUrl);

        // お気に入りが登録されている
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 戻った書籍詳細から読書計画一覧へ戻れる
        $response = $this->actingAs($user)->get($detailUrl);

        $expectedBackUrl = route('reading-plans.index', [
            'status' => 'in_progress',
        ]);

        $response->assertSee(
            'href="'.e($expectedBackUrl).'"',
            false
        );
    }

    /**
     * 読書計画一覧から開いた書籍のお気に入りを解除しても、
     * 遷移元と状態フィルターが保持される。
     */
    public function test_removing_favorite_preserves_reading_plan_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // あらかじめお気に入りに登録
        $user->favoriteBooks()->attach($book->id);

        // 読書計画一覧から遷移した書籍詳細URL
        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'reading-plans',
            'status' => 'in_progress',
        ]);

        // お気に入り解除前の書籍詳細を表示
        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertOk();

        // 解除用フォームに遷移元と状態フィルターが保持される
        $response->assertSee(
            'name="from" value="reading-plans"',
            false
        );

        $response->assertSee(
            'name="status" value="in_progress"',
            false
        );

        // お気に入りを解除
        $response = $this->actingAs($user)->post(
            route('favorites.toggle', $book),
            [
                'from' => 'reading-plans',
                'status' => 'in_progress',
            ]
        );

        // 遷移元とフィルターを保持した書籍詳細へ戻る
        $response->assertRedirect($detailUrl);

        // お気に入りが削除されている
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 書籍詳細から元の読書計画一覧へ戻れる
        $response = $this->actingAs($user)->get($detailUrl);

        $expectedBackUrl = route('reading-plans.index', [
            'status' => 'in_progress',
        ]);

        $response->assertSee(
            'href="'.e($expectedBackUrl).'"',
            false
        );
    }

    /**
     * 読書計画一覧から開いた書籍のレビューにいいねしても、
     * 遷移元と状態フィルターが保持される。
     */
    public function test_adding_review_like_preserves_reading_plan_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // ログインユーザーの読書計画を作成
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // いいね対象となる他ユーザーのレビュー
        $review = Review::factory()
            ->for($otherUser)
            ->for($book)
            ->create(['rating' => 5]);

        // 読書計画一覧から遷移した書籍詳細URL
        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'reading-plans',
            'status' => 'in_progress',
        ]);

        // 書籍詳細を表示
        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertOk();

        // いいねフォームに遷移元と状態が保持される
        $response->assertSee(
            'name="from" value="reading-plans"',
            false
        );

        $response->assertSee(
            'name="status" value="in_progress"',
            false
        );

        // 他ユーザーのレビューにいいねする
        $response = $this->actingAs($user)->post(
            route('reviews.like', $review),
            [
                'from' => 'reading-plans',
                'status' => 'in_progress',
            ]
        );

        // いいねが登録される
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 遷移元と状態を保持した書籍詳細へ戻る
        $response->assertRedirect($detailUrl);

        // 書籍詳細から元の読書計画一覧へ戻れる
        $response = $this->actingAs($user)->get($detailUrl);

        $expectedBackUrl = route('reading-plans.index', [
            'status' => 'in_progress',
        ]);

        $response->assertSee(
            'href="'.e($expectedBackUrl).'"',
            false
        );
    }

    /**
     * 読書計画一覧から開いた書籍のレビューのいいねを解除しても、
     * 遷移元と状態フィルターが保持される。
     */
    public function test_removing_review_like_preserves_reading_plan_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // ログインユーザーの読書計画を作成
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // いいね対象となる他ユーザーのレビュー
        $review = Review::factory()
            ->for($otherUser)
            ->for($book)
            ->create(['rating' => 5]);

        // あらかじめいいねを登録
        $user->likedReviews()->attach($review->id);

        // 読書計画一覧から遷移した書籍詳細URL
        $detailUrl = route('books.show', [
            'book' => $book,
            'from' => 'reading-plans',
            'status' => 'in_progress',
        ]);

        // いいね解除前の書籍詳細を表示
        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertOk();

        // 解除用フォームに遷移元と状態が保持される
        $response->assertSee(
            'name="from" value="reading-plans"',
            false
        );

        $response->assertSee(
            'name="status" value="in_progress"',
            false
        );

        // いいねを解除
        $response = $this->actingAs($user)->post(
            route('reviews.like', $review),
            [
                'from' => 'reading-plans',
                'status' => 'in_progress',
            ]
        );

        // いいねが削除されている
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 遷移元と状態を保持した書籍詳細へ戻る
        $response->assertRedirect($detailUrl);

        // 書籍詳細から元の読書計画一覧へ戻れる
        $response = $this->actingAs($user)->get($detailUrl);

        $expectedBackUrl = route('reading-plans.index', [
            'status' => 'in_progress',
        ]);

        $response->assertSee(
            'href="'.e($expectedBackUrl).'"',
            false
        );
    }

    /**
     * 状態フィルターが未指定の場合も、
     * 書籍詳細から読書計画一覧へ戻れる。
     */
    public function test_book_detail_back_link_without_status_filter(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        // 状態フィルターを指定せず読書計画一覧を表示
        $response = $this->actingAs($user)->get(
            route('reading-plans.index')
        );

        $response->assertOk();

        // 書籍詳細へのリンクに遷移元が設定される
        $detailUrl = route('books.show', [
            'book' => $plan->book,
            'from' => 'reading-plans',
            'status' => '',
        ]);

        $response->assertSee(
            'href="'.e($detailUrl).'"',
            false
        );

        // 書籍詳細を表示
        $response = $this->get($detailUrl);

        $response->assertOk();

        // 状態フィルターなしの読書計画一覧へ戻れる
        $response->assertSee(
            'href="'.route('reading-plans.index').'"',
            false
        );
    }
}
