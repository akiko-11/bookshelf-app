<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Services\ReadingPlanExpirationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanExpirationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 期日を過ぎた進行中の読書計画は期限切れに更新される
     */
    public function test_overdue_plan_is_marked_as_expired(): void
    {
        // 日本時間の2026年11月2日に固定
        $this->travelTo(
            Carbon::parse('2026-11-02 12:00:00', 'Asia/Tokyo')
        );

        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $updatedCount = $service->expireOverduePlans();

        // 更新件数
        $this->assertSame(1, $updatedCount);

        // DBのステータス
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }

    /**
     * 更新対象外の読書計画はステータスを変更しない
     */
    public function test_non_overdue_or_non_in_progress_plans_are_not_updated(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-02 12:00:00', 'Asia/Tokyo')
        );

        // 当日期限の進行中の計画
        $todayPlan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-02',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 未来期限の進行中の計画
        $futurePlan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 完了済みの計画
        $completedPlan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        // すでに期限切れの計画
        $expiredPlan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::Expired,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $updatedCount = $service->expireOverduePlans();

        // 更新件数は0件
        $this->assertSame(0, $updatedCount);

        // それぞれのステータスが変わっていない
        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $todayPlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $futurePlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $completedPlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $expiredPlan->fresh()->status
        );
    }

    /**
     * 期日の3日前・当日・3日後にリマインダー通知が生成される
     */
    public function test_reminders_are_sent_at_three_timings(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-02 12:00:00', 'Asia/Tokyo')
        );

        $user = User::factory()->create();

        // 3日前の通知対象
        $beforePlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 当日の通知対象
        $todayPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-02',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 3日後の通知対象
        $afterPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-10-30',
            'status' => ReadingPlanStatus::Expired,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $sentCount = $service->sendReminders();

        // 通知が3件生成されたことを確認
        $this->assertSame(3, $sentCount);
        $this->assertDatabaseCount('notifications', 3);

        // 3日前通知
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $beforePlan->id,
            'data->timing' => 'before_3_days',
        ]);

        // 当日通知
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $todayPlan->id,
            'data->timing' => 'on_due_date',
        ]);

        // 3日後通知
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $afterPlan->id,
            'data->timing' => 'after_3_days',
        ]);
    }

    /**
     * 同日に再実行してもリマインダー通知が重複しない
     */
    public function test_reminders_are_not_duplicated_on_same_day(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-02 12:00:00', 'Asia/Tokyo')
        );

        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        // 1回目：通知を生成
        $firstCount = $service->sendReminders();

        // 2回目：同じ条件の通知は生成しない
        $secondCount = $service->sendReminders();

        $this->assertSame(1, $firstCount);
        $this->assertSame(0, $secondCount);

        // 通知が1件だけ保存されている
        $this->assertDatabaseCount('notifications', 1);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $plan->id,
            'data->target_date' => '2026-11-05',
            'data->timing' => 'before_3_days',
        ]);
    }

    /**
     * 完了済み計画には通知しない
     */
    public function test_completed_plan_does_not_receive_reminder(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-02 12:00:00', 'Asia/Tokyo')
        );

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $sentCount = $service->sendReminders();

        // 完了済みの計画には通知を生成しない
        $this->assertSame(0, $sentCount);

        $this->assertDatabaseCount('notifications', 0);
    }

    /**
     * バッチ実行漏れ後は期限切れ状態を更新し、過去の通知は生成しない
     */
    public function test_missed_batch_updates_status_without_backfilling_reminders(): void
    {
        // 11月3日の通知実行が漏れ、11月4日に実行された想定
        $this->travelTo(
            Carbon::parse('2026-11-04 20:00:00', 'Asia/Tokyo')
        );

        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-10-31',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        // 期限切れへの更新
        $updatedCount = $service->expireOverduePlans();

        // 通知生成
        $sentCount = $service->sendReminders();

        // 期限切れへ更新される
        $this->assertSame(1, $updatedCount);
        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        // 11月3日（期日の3日後）の通知は遡って生成しない
        $this->assertSame(0, $sentCount);
        $this->assertDatabaseCount('notifications', 0);
    }

    /**
     * 期日変更後は新しい期日を基準に通知を生成する
     */
    public function test_reminders_use_updated_target_date(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-02 20:00:00', 'Asia/Tokyo')
        );

        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-02',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 期日を11月2日から11月5日に変更
        $plan->update([
            'target_date' => '2026-11-05',
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $sentCount = $service->sendReminders();

        // 変更後の期日を基準に通知が1件生成される
        $this->assertSame(1, $sentCount);

        $this->assertDatabaseHas('notifications', [
            'data->reading_plan_id' => $plan->id,
            'data->target_date' => '2026-11-05',
            'data->timing' => 'before_3_days',
        ]);

        // 変更前の期日に基づく当日通知は生成されない
        $this->assertDatabaseMissing('notifications', [
            'data->reading_plan_id' => $plan->id,
            'data->target_date' => '2026-11-02',
            'data->timing' => 'on_due_date',
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 通知のタイトルと本文に書籍名・目標期日が含まれる
     */
    public function test_reminder_contains_book_title_and_target_date(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-02 20:00:00', 'Asia/Tokyo')
        );

        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $sentCount = $service->sendReminders();

        $this->assertSame(1, $sentCount);

        // DBに保存された通知を取得
        $notification = $plan->user->notifications()->firstOrFail();

        $data = $notification->data;
        $bookTitle = $plan->book->title;
        $targetDate = $plan->target_date->format('Y-m-d');

        // タイトルに書籍名・目標期日が含まれる
        $this->assertStringContainsString($bookTitle, $data['title']);
        $this->assertStringContainsString($targetDate, $data['title']);

        // 本文に書籍名・目標期日が含まれる
        $this->assertStringContainsString($bookTitle, $data['message']);
        $this->assertStringContainsString($targetDate, $data['message']);
    }
}
