<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍が未選択の場合はバリデーションエラーになる
     */
    public function test_book_is_required(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => '',
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasErrors(['book_id' => '書籍を選択してください']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 書籍IDが整数でない場合はバリデーションエラーになる
     */
    public function test_book_id_must_be_an_integer(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => 'abc',
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasErrors(['book_id' => '書籍IDは整数で入力してください']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 存在しない書籍IDの場合はバリデーションエラーになる
     */
    public function test_book_must_exist(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => 99999,
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasErrors(['book_id' => '選択された書籍が存在しません']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 期日が未入力の場合はバリデーションエラーになる
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日を入力してください']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 期日が不正な日付形式の場合はバリデーションエラーになる
     */
    public function test_target_date_must_be_valid(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2026/11/11',
        ]);

        $response->assertSessionHasErrors(['target_date' => '有効な日付を入力してください']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 過去の期日は登録できない
     */
    public function test_target_date_cannot_be_in_the_past(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 12:00:00'));

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2026-11-11',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は今日以降の日付を指定してください']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 同じユーザー・同じ書籍の進行中計画は重複登録できない
     */
    public function test_user_cannot_create_duplicate_in_progress_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasErrors(['book_id' => 'この書籍の読書計画は既に登録されています']);

        $this->assertDatabaseCount('reading_plans', 1);
    }

    /**
     * 完了済み計画と同じ書籍で新規登録できる
     */
    public function test_user_can_create_plan_for_completed_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

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

        $this->assertDatabaseCount('reading_plans', 2);
    }

    /**
     * 期限切れ計画と同じ書籍で新規登録できる
     */
    public function test_user_can_create_plan_for_expired_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);

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

        $this->assertDatabaseCount('reading_plans', 2);
    }

    /**
     * 別ユーザーが進行中の同じ書籍でも登録できる
     */
    public function test_different_users_can_create_plans_for_same_book(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

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

        $this->assertDatabaseCount('reading_plans', 2);
    }
}
