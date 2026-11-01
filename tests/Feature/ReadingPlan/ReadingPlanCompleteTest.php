<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCompleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者は進行中の読書計画を読了できる
     */
    public function test_owner_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を読了しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /**
     * 読了時にステータスと完了日時が正しく保存される
     */
    public function test_completion_updates_status_and_completed_at(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 14:00:00'));

        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertNotNull($plan->completed_at);

        $this->assertSame(
            '2026-11-01 14:00:00',
            $plan->completed_at->format('Y-m-d H:i:s')
        );
    }

    /**
     * 未認証ユーザーは読書計画を読了できない
     */
    public function test_guest_cannot_complete_reading_plan(): void
    {
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->post(
            route('reading-plans.complete', $plan)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    /**
     * 他ユーザーの読書計画は読了できない
     */
    public function test_user_cannot_complete_another_users_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $otherUser->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);

        $response->assertForbidden();
        $response->assertSeeText('この読書計画を操作する権限がありません。');
    }

    /**
     * 完了済みの読書計画は再度読了できない
     */
    public function test_completed_plan_cannot_be_completed_again(): void
    {
        $user = User::factory()->create();
        $completedAt = now()->subDays(2)->toDateTimeString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => $completedAt,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        $response->assertStatus(409);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => $completedAt,
        ]);

        $response->assertStatus(409);
        $response->assertSeeText('この読書計画は既に完了しています。');
    }

    /**
     * 存在しない読書計画は読了できない
     */
    public function test_nonexistent_plan_returns_404_on_complete(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', 999999));

        $response->assertNotFound();

        $this->assertDatabaseCount('reading_plans', 0);

        $response->assertNotFound();
        $response->assertSeeText('読書計画が見つかりません。');
    }
}
