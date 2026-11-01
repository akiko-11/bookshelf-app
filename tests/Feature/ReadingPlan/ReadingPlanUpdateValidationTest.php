<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 期日が未入力の場合はバリデーションエラーになる
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $originalDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '',
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日を入力してください',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
        ]);
    }

    /**
     * 期日が不正な日付形式の場合はバリデーションエラーになる
     */
    public function test_target_date_must_be_valid(): void
    {
        $user = User::factory()->create();
        $originalDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026/11/11',
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は有効な日付形式で入力してください',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
        ]);
    }

    /**
     * 存在しない日付は指定できない
     */
    public function test_target_date_must_be_a_real_date(): void
    {
        $user = User::factory()->create();
        $originalDate = now()->addDays(7)->toDateString();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-02-30',
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は有効な日付形式で入力してください',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
        ]);
    }

    /**
     * 過去の期日は指定できない
     */
    public function test_target_date_cannot_be_in_the_past(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 12:00:00'));

        $user = User::factory()->create();
        $originalDate = '2026-11-20';

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-11-11',
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
        ]);
    }
}
