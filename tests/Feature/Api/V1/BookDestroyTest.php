<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    // 所有者は書籍を物理削除できる
    public function test_owner_can_destroy_book(): void
    {
        $user = $this->createAuthenticatedUser();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    // 書籍削除時にジャンルとの関連付けが削除される
    public function test_destroy_book_detaches_genres(): void
    {
        $user = $this->createAuthenticatedUser();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    // 書籍削除時にレビューが削除され、投稿者は維持される
    public function test_destroy_book_deletes_reviews_but_keeps_users(): void
    {
        $user = $this->createAuthenticatedUser();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $reviewer->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    // 書籍削除時にレビューのいいね情報も削除される
    public function test_destroy_book_deletes_review_likes(): void
    {
        $user = $this->createAuthenticatedUser();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $liker = User::factory()->create();

        $reviewLike = ReviewLike::create([
            'user_id' => $liker->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'id' => $reviewLike->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);
    }

    // 書籍削除時にお気に入り情報が削除される
    public function test_destroy_book_deletes_favorites(): void
    {
        $user = $this->createAuthenticatedUser();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $favoriteUser = User::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $favoriteUser->id,
        ]);
    }

    // 存在しない書籍の削除で404を返す
    public function test_destroy_nonexistent_book_returns_404(): void
    {
        $this->createAuthenticatedUser();

        $response = $this->deleteJson(
            '/api/v1/books/99999'
        );

        $response->assertStatus(404);

        $response->assertExactJson([
            'message' => '指定された書籍が見つかりません。',
        ]);
    }

    /**
     * 未認証ユーザーは書籍を削除できず、
     * 401と指定されたJSONメッセージが返される。
     */
    public function test_guest_cannot_delete_book(): void
    {
        // 削除対象の書籍を作成
        $book = Book::factory()->create();

        // 未認証で削除リクエストを送信
        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        // HTTP 401とJSONメッセージを確認
        $response->assertUnauthorized();

        $response->assertExactJson([
            'message' => '認証が必要です。',
        ]);

        $response->assertHeader(
            'Content-Type',
            'application/json'
        );

        // 書籍が削除されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * 無効なBearerトークンでは書籍を削除できず、
     * 401と指定されたJSONメッセージが返される。
     */
    public function test_invalid_token_cannot_delete_book(): void
    {
        // 削除対象の書籍を作成
        $book = Book::factory()->create();

        // 無効なBearerトークンで削除リクエスト
        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}",
            [],
            [
                'Authorization' => 'Bearer invalid-token',
            ]
        );

        // HTTP 401とJSONメッセージを確認
        $response->assertUnauthorized();

        $response->assertExactJson([
            'message' => '認証が必要です。',
        ]);

        $response->assertHeader(
            'Content-Type',
            'application/json'
        );

        // 書籍が削除されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * 認証済みでも他人の書籍は削除できず、
     * 403と指定されたJSONメッセージが返される。
     */
    public function test_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        // 所有者の書籍を作成
        $book = Book::factory()
            ->for($owner)
            ->create();

        // 別ユーザーのBearerトークンで認証
        $token = $otherUser->createToken('test-token')->plainTextToken;
        $this->withToken($token);

        // 他人の書籍を削除しようとする
        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        // HTTP 403と指定されたJSONを確認
        $response->assertForbidden();

        $response->assertExactJson([
            'message' => 'この書籍を操作する権限がありません。',
        ]);

        $response->assertHeader(
            'Content-Type',
            'application/json'
        );

        // 書籍が削除されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }

    /**
     * APIで書籍を削除した際、
     * 関連する読書計画と既読・未読通知が削除される。
     * 他の書籍に関連するデータは保持する。
     */
    public function test_destroy_book_deletes_reading_plans_and_notifications(): void
    {
        $user = $this->createAuthenticatedUser();

        // 削除対象の書籍
        $book = Book::factory()
            ->for($user)
            ->create();

        // 削除対象の読了済み計画
        $plan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        // 削除対象ではない書籍と読書計画
        $otherBook = Book::factory()
            ->for($user)
            ->create();

        $otherPlan = ReadingPlan::factory()
            ->for($user)
            ->for($otherBook)
            ->create();

        $unreadId = (string) Str::uuid();
        $readId = (string) Str::uuid();
        $otherId = (string) Str::uuid();

        $now = now();

        // 削除対象の未読・既読通知と、別計画の通知
        DB::table('notifications')->insert([
            [
                'id' => $unreadId,
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'reading_plan_id' => $plan->id,
                ]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $readId,
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'reading_plan_id' => $plan->id,
                ]),
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $otherId,
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'reading_plan_id' => $otherPlan->id,
                ]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 削除前に通知が3件存在する
        $this->assertDatabaseCount('notifications', 3);

        // APIから書籍を削除
        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        // 対象書籍・読書計画が削除される
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);

        // 対象計画の未読通知・既読通知が削除される
        $this->assertDatabaseMissing('notifications', [
            'id' => $unreadId,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $readId,
        ]);

        // 無関係な書籍・読書計画・通知は残る
        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherPlan->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $otherId,
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 複数ユーザーの読書計画が同じ書籍に紐づく場合も、
     * すべての関連計画と通知が削除される。
     */
    public function test_destroy_book_deletes_multiple_reading_plans_and_notifications(): void
    {
        $owner = $this->createAuthenticatedUser();
        $otherUser = User::factory()->create();

        // 削除対象の書籍
        $book = Book::factory()->for($owner)->create();

        // それぞれのユーザーの読書計画
        $planA = ReadingPlan::factory()
            ->for($owner)
            ->for($book)
            ->create();

        $planB = ReadingPlan::factory()
            ->for($otherUser)
            ->for($book)
            ->create();

        // 削除対象外の書籍と読書計画
        $otherBook = Book::factory()->for($owner)->create();

        $otherPlan = ReadingPlan::factory()
            ->for($owner)
            ->for($otherBook)
            ->create();

        $now = now();

        // 各読書計画に通知を作成
        foreach ([
            $planA,
            $planB,
            $otherPlan,
        ] as $plan) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $plan->user->getMorphClass(),
                'notifiable_id' => $plan->user_id,
                'data' => json_encode([
                    'reading_plan_id' => $plan->id,
                ]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->assertDatabaseCount('notifications', 3);

        // APIから書籍を削除
        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        // 削除対象の読書計画が両方削除される
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $planA->id,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $planB->id,
        ]);

        // 関連通知がすべて削除される
        $this->assertDatabaseCount('notifications', 1);

        // 別の書籍と読書計画は保持される
        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherPlan->id,
        ]);

        // 残った通知は別の読書計画のもの
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'data->reading_plan_id' => $otherPlan->id,
        ]);
    }

    /**
     * テスト用ユーザーを作成し、Bearerトークンで認証する。
     */
    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token);

        return $user;
    }
}
