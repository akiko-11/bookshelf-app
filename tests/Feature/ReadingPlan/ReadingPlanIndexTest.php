<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 無効な状態が指定された場合は422を返す。
     */
    public function test_returns_422_for_invalid_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=invalid');

        $response->assertStatus(422);
        $response->assertSee('指定された状態は無効です。');
    }

    /**
     * 状態に配列が指定された場合は422を返す。
     */
    public function test_returns_422_for_array_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reading-plans?status[]=in_progress');

        $response->assertStatus(422);
        $response->assertSee('指定された状態は無効です。');
    }

    /**
     * 数値形式の無効な状態が指定された場合は422を返す。
     */
    public function test_returns_422_for_numeric_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=123');

        $response->assertStatus(422);
        $response->assertSee('指定された状態は無効です。');
    }

    /**
     * 状態が未指定の場合はすべての読書計画を表示できる。
     */
    public function test_displays_all_reading_plans_without_status(): void
    {
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($plans) {
            return $plans->count() === 2;
        });
    }

    /**
     * 状態が空文字の場合はすべての読書計画を表示できる。
     */
    public function test_displays_all_reading_plans_with_empty_status(): void
    {
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=');

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($plans) {
            return $plans->count() === 2;
        });
    }

    /**
     * 進行中の読書計画だけを表示できる。
     */
    public function test_filters_reading_plans_by_in_progress_status(): void
    {
        $user = User::factory()->create();

        [$inProgressPlan, $completedPlan, $expiredPlan] =
            $this->createReadingPlansForUser($user);

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=in_progress');

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($plans) use ($inProgressPlan) {
            return $plans->count() === 1
                && $plans->first()->id === $inProgressPlan->id;
        });
    }

    /**
     * 完了済みの読書計画だけを表示できる。
     */
    public function test_filters_reading_plans_by_completed_status(): void
    {
        $user = User::factory()->create();

        [$inProgressPlan, $completedPlan, $expiredPlan] =
            $this->createReadingPlansForUser($user);

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=completed');

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($plans) use ($completedPlan) {
            return $plans->count() === 1
                && $plans->first()->id === $completedPlan->id;
        });
    }

    /**
     * 期限切れの読書計画だけを表示できる。
     */
    public function test_filters_reading_plans_by_expired_status(): void
    {
        $user = User::factory()->create();

        [$inProgressPlan, $completedPlan, $expiredPlan] =
            $this->createReadingPlansForUser($user);

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=expired');

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($plans) use ($expiredPlan) {
            return $plans->count() === 1
                && $plans->first()->id === $expiredPlan->id;
        });
    }

    /**
     * 未認証ユーザーは読書計画一覧にアクセスできない。
     */
    public function test_guest_cannot_access_reading_plans_index(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * ログインユーザー自身の読書計画のみ表示される。
     */
    public function test_displays_only_authenticated_users_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($plans) use ($ownPlan) {
            return $plans->contains('id', $ownPlan->id);
        });

        $response->assertViewHas('readingPlans', function ($plans) use ($otherPlan) {
            return ! ($plans->contains('id', $otherPlan->id));
        });
    }

    /**
     * 読書計画が期日の昇順で表示される。
     */
    public function test_displays_reading_plans_ordered_by_target_date(): void
    {
        $user = User::factory()->create();

        $thirdPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-10',
        ]);

        $firstPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-03',
        ]);

        $secondPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-07',
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();

        $response->assertViewHas(
            'readingPlans',
            function ($plans) use ($firstPlan, $secondPlan, $thirdPlan) {
                return $plans->pluck('id')->all() === [
                    $firstPlan->id,
                    $secondPlan->id,
                    $thirdPlan->id,
                ];
            }
        );
    }

    /**
     * 状態で絞り込んだ場合も期日の昇順で表示される。
     */
    public function test_filtered_reading_plans_are_ordered_by_target_date(): void
    {
        $user = User::factory()->create();

        $thirdPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-10',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $firstPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-03',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::Completed,
        ]);

        $secondPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-07',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=in_progress');

        $response->assertOk();

        $response->assertViewHas(
            'readingPlans',
            function ($plans) use ($firstPlan, $secondPlan, $thirdPlan) {
                return $plans->pluck('id')->all() === [
                    $firstPlan->id,
                    $secondPlan->id,
                    $thirdPlan->id,
                ];
            }
        );
    }

    /**
     * 読書計画が0件でも一覧画面を表示できる。
     */
    public function test_displays_index_when_no_reading_plans_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();

        $response->assertViewHas(
            'readingPlans',
            function ($plans) {
                return $plans->isEmpty();
            }
        );
    }

    /**
     * 選択された状態フィルターがViewに渡される。
     */
    public function test_passes_current_status_to_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=in_progress');

        $response->assertOk();
        $response->assertViewHas('currentStatus', 'in_progress');
    }

    /**
     * 指定ユーザーに3種類の状態の読書計画を作成する。
     *
     * @return array{ReadingPlan, ReadingPlan, ReadingPlan}
     */
    private function createReadingPlansForUser(User $user): array
    {
        $inProgressPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Expired,
        ]);

        return [$inProgressPlan, $completedPlan, $expiredPlan];
    }
}
