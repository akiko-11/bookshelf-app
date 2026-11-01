<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者は進行中の読書計画の編集画面を表示できる
     */
    public function test_owner_can_access_edit_page(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        $response->assertOk();

        $response->assertViewIs('reading-plans.edit');

        $response->assertViewHas('readingPlan', function ($value) use ($plan) {
            return $value->is($plan);
        });
    }

    /**
     * 編集画面には登録済みの期日と書籍情報が表示される
     */
    public function test_edit_page_displays_existing_plan_data(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        $response->assertOk();

        $response->assertSeeText($plan->book->title);
        $response->assertSeeText('進行中');
        $response->assertSee('value="'.$targetDate.'"', false);
    }

    /**
     * 未認証ユーザーは編集画面にアクセスできない
     */
    public function test_guest_cannot_access_edit_page(): void
    {
        $plan = ReadingPlan::factory()->create();

        $response = $this->get(route('reading-plans.edit', $plan));

        $response->assertRedirect(route('login'));
    }

    /**
     * 他ユーザーの読書計画は編集できない
     */
    public function test_user_cannot_edit_another_users_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        $response->assertForbidden();
    }

    /**
     * 完了済みの読書計画は編集できない
     */
    public function test_completed_plan_cannot_be_edited(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        $response->assertForbidden();
    }

    /**
     * 存在しない読書計画の編集画面は404になる
     */
    public function test_nonexistent_plan_returns_404_on_edit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', 999999));

        $response->assertNotFound();
    }

    /**
     * 期日を過ぎた期限切れの読書計画は編集できない
     */
    public function test_expired_plan_past_target_date_cannot_be_edited(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        $response->assertForbidden();
    }
}
