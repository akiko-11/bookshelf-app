<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use App\Services\ReadingPlanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;

class ReadingPlanStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーは読書計画を登録できる
     */
    public function test_authenticated_user_can_create_a_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasNoErrors();

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas('success', '読書計画を登録しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    /**
     * 未認証ユーザーは読書計画の登録画面にアクセスできない
     */
    public function test_guest_cannot_access_create_page(): void
    {
        $response = $this->get(route('reading-plans.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 未認証ユーザーは読書計画を登録できない
     */
    public function test_guest_cannot_create_a_reading_plan(): void
    {
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * DB登録エラー時は作成画面に戻り、エラーメッセージを表示する
     */
    public function test_database_error_redirects_back_with_error_message(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        // ServiceでDBエラーが発生する状況を再現
        $this->mock(ReadingPlanService::class)
            ->shouldReceive('createForUser')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'insert into reading_plans',
                [],
                new PDOException('Simulated database error')
            ));

        // Act
        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        // Assert: 登録画面に戻る
        $response->assertRedirect(route('reading-plans.create'));

        // Assert: エラーメッセージを確認
        $response->assertSessionHas(
            'error',
            '読書計画の登録に失敗しました。'
        );

        // Assert: 入力内容が保持される
        $response->assertSessionHas('_old_input.book_id', $book->id);
        $response->assertSessionHas('_old_input.target_date', $targetDate);

        // Assert: DBに登録されていない
        $this->assertDatabaseCount('reading_plans', 0);
    }
}
