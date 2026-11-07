<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Exceptions\ReadingPlanAlreadyCompletedException;
use App\Models\ReadingPlan;
use App\Services\ReadingPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviceから進行中の読書計画を正常に読了できる
     */
    public function test_service_can_complete_in_progress_plan(): void
    {
        $this->travelTo(Carbon::parse('2026-11-03 14:00:00'));

        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $service = app(ReadingPlanService::class);

        $result = $service->completePlan($plan);

        $result->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $result->status
        );

        $this->assertSame(
            '2026-11-03 14:00:00',
            $result->completed_at->format('Y-m-d H:i:s')
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /**
     * Serviceから完了済みの読書計画を再読了できない
     */
    public function test_service_prevents_recompletion_and_preserves_completed_at(): void
    {
        $this->travelTo(Carbon::parse('2026-11-03 14:00:00'));

        $completedAt = '2026-11-01 14:00:00';

        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => $completedAt,
        ]);

        $service = app(ReadingPlanService::class);

        // 完了済み計画に対して再度読了処理を実行
        try {
            $service->completePlan($plan);

            // 例外が発生しなかった場合はテスト失敗
            $this->fail('完了済みの読書計画が再読了されました。');
        } catch (ReadingPlanAlreadyCompletedException) {
            // 期待した例外が発生したため、検証を続行
        }

        $plan->refresh();

        // ステータスが完了のままであることを確認
        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        // 完了日時が変更されていないことを確認
        $this->assertSame(
            $completedAt,
            $plan->completed_at->format('Y-m-d H:i:s')
        );

        // DB上でも更新されていないことを確認
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => $completedAt,
        ]);
    }
}
