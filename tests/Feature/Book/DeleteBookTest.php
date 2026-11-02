<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use App\Services\ReadingPlanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDOException;
use Tests\TestCase;

class DeleteBookTest extends TestCase
{
    use RefreshDatabase;

    // 登録者本人が書籍を削除できる
    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    // 削除後に書籍一覧へ遷移し、FlashMessageが表示される
    public function test_deleting_book_redirects_to_index_with_success_message(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );
    }

    // 他ユーザーは削除できず403になる
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 未ログインでは削除できずログイン画面に遷移する
    public function test_guest_cannot_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->delete("/books/{$book->id}");

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 書籍削除時に関連データも削除される
    public function test_deleting_book_removes_related_data(): void
    {
        $user = User::factory()->create();
        $favoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $book->genres()->attach($genre->id);

        $favoriteUser->favoriteBooks()->attach($book->id);

        DB::table('review_likes')->insert([
            'user_id' => $favoriteUser->id,
            'review_id' => $review->id,
        ]);

        // いいねが登録されていることを確認
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $favoriteUser->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        // ジャンル自体は削除されない
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        // 書籍とジャンルの紐付けが削除されている
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $favoriteUser->id,
            'review_id' => $review->id,
        ]);
    }

    /**
     * 書籍削除時に関連する読書計画と通知も削除される
     */
    public function test_deleting_book_removes_reading_plans_and_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        // 削除対象の書籍に紐づく読書計画
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $otherUserPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
        ]);

        // 別の書籍に紐づく読書計画
        $unrelatedPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $otherBook->id,
        ]);

        $readNotificationId = (string) Str::uuid();
        $unreadNotificationId = (string) Str::uuid();
        $unrelatedNotificationId = (string) Str::uuid();

        $now = now();

        // 既読・未読の関連通知と、無関係な通知を登録
        DB::table('notifications')->insert([
            [
                'id' => $readNotificationId,
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode(['reading_plan_id' => $plan->id]),
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $unreadNotificationId,
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $otherUser->getMorphClass(),
                'notifiable_id' => $otherUser->id,
                'data' => json_encode(['reading_plan_id' => $otherUserPlan->id]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $unrelatedNotificationId,
                'type' => 'reading_plan_reminder',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode(['reading_plan_id' => $unrelatedPlan->id]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        // 削除対象の書籍と関連計画が削除される
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('reading_plans', ['id' => $otherUserPlan->id]);

        // 既読・未読の関連通知も削除される
        $this->assertDatabaseMissing('notifications', ['id' => $readNotificationId]);
        $this->assertDatabaseMissing('notifications', ['id' => $unreadNotificationId]);

        // 無関係な書籍・計画・通知は残る
        $this->assertDatabaseHas('books', ['id' => $otherBook->id]);
        $this->assertDatabaseHas('reading_plans', ['id' => $unrelatedPlan->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unrelatedNotificationId]);
    }

    /**
     * 書籍削除中にDBエラーが発生した場合、関連データを保持する
     */
    public function test_book_delete_failure_rolls_back_related_data(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'reading_plan_reminder',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'reading_plan_id' => $plan->id,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 実際のServiceで関連データを削除した後、DBエラーを再現
        $realService = new ReadingPlanService;

        $this->mock(ReadingPlanService::class)
            ->shouldReceive('deletePlan')
            ->once()
            ->andReturnUsing(function (ReadingPlan $readingPlan) use ($realService): void {
                $realService->deletePlan($readingPlan);

                throw new QueryException(
                    'mysql',
                    'DELETE FROM reading_plans WHERE id = ?',
                    [$readingPlan->id],
                    new PDOException('テスト用DBエラー')
                );
            });

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        // エラー時は書籍詳細画面へ戻る
        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'error',
            '書籍の削除に失敗しました。'
        );

        // ロールバックによりすべてのデータが残る
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
        ]);
    }
}
