<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Services\ReadingPlanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;

class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者は読書計画の期日を更新できる
     */
    public function test_owner_can_update_reading_plan(): void
    {
        $user = User::factory()->create();
        $newDate = now()->addDays(14)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newDate,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を更新しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $user->id,
            'target_date' => $newDate,
        ]);
    }

    /**
     * 更新時に書籍・状態・完了日は変更されない
     */
    public function test_update_only_changes_target_date(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $newDate = now()->addDays(14)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newDate,
                'user_id' => $otherUser->id,
                'book_id' => $otherBook->id,
                'status' => ReadingPlanStatus::Completed->value,
                'completed_at' => now()->toDateTimeString(),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame($user->id, $plan->user_id);
        $this->assertSame($book->id, $plan->book_id);
        $this->assertSame($newDate, $plan->target_date->format('Y-m-d'));
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);
        $this->assertNull($plan->completed_at);
    }

    /**
     * 未認証ユーザーは読書計画を更新できない
     */
    public function test_guest_cannot_update_reading_plan(): void
    {
        $originalDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'target_date' => $originalDate,
        ]);

        $response = $this->put(
            route('reading-plans.update', $plan),
            ['target_date' => now()->addDays(14)->toDateString()]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
        ]);
    }

    /**
     * 他ユーザーの読書計画は更新できない
     */
    public function test_user_cannot_update_another_users_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $originalDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $otherUser->id,
            'target_date' => $originalDate,
        ]);
    }

    /**
     * 完了済みの読書計画は更新できない
     */
    public function test_completed_plan_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $originalDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /**
     * 存在しない読書計画は更新できない
     */
    public function test_nonexistent_plan_returns_404_on_update(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', 999999), [
                'target_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertNotFound();
    }

    /**
     * DB更新エラー時は編集画面に戻りエラーメッセージを表示する
     */
    public function test_database_error_redirects_back_with_error_message(): void
    {
        $user = User::factory()->create();
        $originalDate = now()->addDays(7)->toDateString();
        $newDate = now()->addDays(14)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->mock(ReadingPlanService::class)
            ->shouldReceive('updatePlan')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'update reading_plans set target_date = ?',
                [$newDate],
                new PDOException('Simulated database error')
            ));

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newDate,
            ]);

        $response->assertRedirect(route('reading-plans.edit', $plan));

        $response->assertSessionHas(
            'error',
            '読書計画の更新に失敗しました。'
        );

        $response->assertSessionHas(
            '_old_input.target_date',
            $newDate
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
        ]);
    }

    /**
     * 期日を過ぎた期限切れの読書計画は更新できない
     */
    public function test_expired_plan_past_target_date_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $originalDate = now()->subDay()->toDateString();
        $newDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newDate,
            ]);

        $response->assertForbidden();

        // DBの期日・ステータスが変更されていないことを確認
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }
}
