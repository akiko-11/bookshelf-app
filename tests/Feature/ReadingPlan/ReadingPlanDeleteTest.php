<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Services\ReadingPlanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDOException;
use Tests\TestCase;

class ReadingPlanDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者が読書計画を削除できる
     */
    public function test_owner_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を削除しました。'
        );

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    /**
     * 未認証ユーザーは読書計画を削除できない
     */
    public function test_guest_cannot_delete_reading_plan(): void
    {
        $plan = ReadingPlan::factory()->create();

        $response = $this->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    /**
     * 他ユーザーの読書計画は削除できない
     */
    public function test_other_user_cannot_delete_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertStatus(403);
        $response->assertSeeText('この読書計画を操作する権限がありません。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    /**
     * 存在しない読書計画は削除できない
     */
    public function test_nonexistent_plan_returns_404_on_delete(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->delete('reading-plans/99999');

        $response->assertNotFound();
        $response->assertSeeText('読書計画が見つかりません。');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 完了済みの読書計画も削除できる
     */
    public function test_owner_can_delete_completed_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    /**
     * 期限切れの読書計画も削除できる
     */
    public function test_owner_can_delete_expired_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Expired,
            'target_date' => today()->subDay(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    /**
     * 読書計画の削除時に関連通知のみ削除される
     */
    public function test_deleting_reading_plan_removes_only_related_notifications(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $unreadNotificationId = (string) Str::uuid();
        $readNotificationId = (string) Str::uuid();
        $otherNotificationId = (string) Str::uuid();

        $now = now();

        // 削除対象の読書計画に紐づく未読通知
        DB::table('notifications')->insert([
            'id' => $unreadNotificationId,
            'type' => 'reading_plan_reminder',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'reading_plan_id' => $plan->id,
            ]),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 削除対象の読書計画に紐づく既読通知
        DB::table('notifications')->insert([
            'id' => $readNotificationId,
            'type' => 'reading_plan_reminder',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'reading_plan_id' => $plan->id,
            ]),
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 別の読書計画に紐づく通知
        DB::table('notifications')->insert([
            'id' => $otherNotificationId,
            'type' => 'reading_plan_reminder',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'reading_plan_id' => $otherPlan->id,
            ]),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertDatabaseCount('notifications', 3);

        // 対象の読書計画を削除
        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        // 対象の読書計画が削除される
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);

        // 対象計画の未読通知が削除される
        $this->assertDatabaseMissing('notifications', [
            'id' => $unreadNotificationId,
        ]);

        // 対象計画の既読通知が削除される
        $this->assertDatabaseMissing('notifications', [
            'id' => $readNotificationId,
        ]);

        // 別の読書計画は残る
        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherPlan->id,
        ]);

        // 別の読書計画の通知は残る
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotificationId,
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * DB削除失敗時はエラーメッセージを表示し、読書計画を残す
     */
    public function test_delete_failure_keeps_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        // DB削除エラーを再現
        $this->mock(ReadingPlanService::class)
            ->shouldReceive('deletePlan')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'DELETE FROM reading_plans WHERE id = ?',
                [$plan->id],
                new PDOException('テスト用DBエラー')
            ));

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'error',
            '読書計画の削除に失敗しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }
}
